<?php

namespace DigidepsBehat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * Behat context class.
 * 
 * when the alpha models are refactored and simplified, this class can be refactored and splitter around.
 * until then, better to keep things in the sample place for simplicity
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    use RegionTrait, 
        DebugTrait,
        StatusSnapshotTrait,
        KernelDictionary;
    
    private static $dbName;
    
    public function __construct(array $options)
    {
        //$options['session']; // not used
        ini_set('xdebug.max_nesting_level', $options['maxNestingLevel'] ?: 200);
        ini_set('max_nesting_level', $options['maxNestingLevel'] ?: 200);
        $this->sessionName = empty($options['sessionName']) ? 'digideps' : $options['sessionName'];
        self::$dbName = empty($options['dbName']) ? null : $options['dbName'];
    }
        
    
    public function setKernel(\AppKernel $kernel)
    {
        $this->kernel = $kernel;
    }
    
    protected function getSymfonyParam($name)
    {
        return $this->getContainer()->getParameter($name);
    }

    
    /**
     * @BeforeSuite
     */
     public static function prepare(\Behat\Testwork\Hook\Scope\BeforeSuiteScope $scope)
     {
         $suiteName = $scope->getSuite()->getName();
         echo "\n\n"
              . strtoupper($suiteName) . "\n"
              . str_repeat('=', strlen($suiteName)) . "\n"
              . $scope->getSuite()->getSetting('description') . "\n"
              . "\n";
     }
    
    /**
     * @Given I am logged in as :email with password :password
     */
    public function iAmLoggedInAsWithPassword($email, $password)
    {
        $this->visitPath('/logout');
        $this->visitPath('/login');
        $this->fillField('login_email',$email);
        $this->fillField('login_password', $password);
        $this->pressButton('login_login');
        $this->assertResponseStatus(200);
    }
    
    
    /**
     * @Given I am on client home :clientHome and I click first report :link
     */
    public function iAmOnClientHomeAndClickReport($clientHome,$link)
    {
        $this->clickOnBehatLink($clientHome);
        $this->clickOnBehatLink($link);
        $this->assertResponseStatus(200);
    }
    
    /**
     * @Given I am on client home page :client_home
     */
    public function iAmOnClientHome($client_home)
    {
        $this->clickOnBehatLink($client_home);
        $this->assertResponseStatus(200);
    }
    
    /**
     * @Given I am not logged in
     */
    public function iAmNotLoggedIn()
    {
        $this->visitPath('/logout');
    }

    /**
     * @Given I am on the login page
     */
    public function iAmAtLogin()
    {
        $this->visitPath('/login');
    }
    
    /**
     * @And I select the feedback link
     */
    public function iPressFeedback()
    {
        $this->visitPath('/feedback');
    }
    
    /**
     * @Then the page title should be :text
     */
    public function thePageTitleShouldBe($text)
    {
        $this->iShouldSeeInTheRegion($text, 'page-title');
    }
    
    
    /**
     * Array (
            [to] => Array(
                    [deputyshipservice@publicguardian.gsi.gov.uk] => test Test
                )

            [from] => Array(
                    [admin@digideps.service.dsd.io ] => Digital deputyship service
                )
            [bcc] =>
            [cc] =>
            [replyTo] =>
            [returnPath] =>
            [subject] => Digideps - activation email
            [body] => Hello test Test, click here http://link.com/activate/testtoken to activate your account
            [sender] =>
            [parts] => Array(
                    [0] => Array(
                            [body] => Hello test Test<br/><br/>click here <a href="http://link.com/activate/testtoken">http://link.com/activate/testtoken</a> to activate your account
                            [contentType] => text/html
                        )
                )
        )
     * 
     * @retun array
     */
    private function getLatestEmailMockFromApi()
    {
        $this->visitBehatLink('email-get-last');
        
        $content =  $this->getSession()->getPage()->getContent();
        $contentJson = json_decode($content, true);
        
        if (empty($contentJson['to'])) {
            throw new \RuntimeException("Email has not been sent. Api returned: " . $content);
        }
        
        return $contentJson;
    }
    
    
    /**
     * @BeforeScenario @cleanMail
     */
    public function beforeScenarioCleanMail(BeforeScenarioScope $scope)
    {
        $this->visitBehatLink('email-reset');
        $this->assertResponseStatus(200);
    }
    
    /**
     * @Then an email with subject :subject should have been sent to :to
     */
    public function anEmailWithSubjectShouldHaveBeenSentTo($subject, $to)
    {
        $mail = $this->getLatestEmailMockFromApi();
        $mailTo = key($mail['to']);
        
        
        if ($mail['subject'] != $subject) {
            throw new \RuntimeException("Subject '" . $mail['subject'] . "' does not match the expected '" . $subject . "'");
        }
        if ($mailTo !== 'the specified email address' && $mailTo != $to) {
            throw new \RuntimeException("Addressee '" . $mailTo . "' does not match the expected '" . $to . "'");
        }
    }
    
     /**
     * @Then an email with subject :subject should have been sent
     */
    public function anEmailWithSubjectShouldHaveBeenSent($subject)
    {
        $mail = $this->getLatestEmailMockFromApi();
        
        if ($mail['subject'] != $subject) {
            throw new \RuntimeException("Subject '" . $mail['subject'] . "' does not match the expected '" . $subject . "'");
        }
    }
    

    /**
     * @When I open the first link on the email
     */
    public function iOpenTheFirstLinkOnTheEmail()
    {
        $mailContent = $this->getLatestEmailMockFromApi()['parts'][0]['body'];
        
        preg_match_all('#https?://[^\s"<]+#', $mailContent, $matches);
        if (empty($matches[0])) {
            throw new \Exception("no link found in email. Body:\n $mailContent");
        }
        $emails = array_unique($matches[0]);
        if (!count($emails)) {
            throw new \Exception("No links found in the email. Body:\n $mailContent");
        }
        $link = array_shift($emails);

        // visit the link
        $this->visit($link);
    }
    
    /**
     * @Then the email should contain :text
     */
    public function mailContainsText($text)
    {
        
        $mailContent = $this->getLatestEmailMockFromApi()['parts'][0]['body'];
        
        if (strpos($mailContent, $text) === FALSE) {
            throw new \Exception("Text: $text not found in email. Body: \n $mailContent");
        }
        
        
    }

    /**
     * @Then the form should contain an error
     */
    public function theFormShouldContainAnError()
    {
        $this->iShouldSeeTheBehatElement('form-errors', 'region');
    }
    
    /**
     * @Then the form should not contain an error
     * @Then the form should not contain any error
     */
    public function theFormShouldNotContainAnError()
    {
        $this->iShouldNotSeeTheBehatElement('form-errors', 'region');
    }
    
    
    /**
     * Check if the given elements (input/textarea inside each .behat-region-form-errors) 
     *  are the only ones with errors 
     * 
     * @Then the following fields should have an error:
     */
    public function theFollowingFieldsOnlyShouldHaveAnError(TableNode $table)
    {
        $fields = array_keys($table->getRowsHash());
        $errorRegionCss = self::behatElementToCssSelector('form-errors', 'region');
        $errorRegions = $this->getSession()->getPage()->findAll('css', $errorRegionCss);
        $foundIdsWithErrors = [];
        foreach ($errorRegions as $errorRegion) {
            $elementsWithErros = $errorRegion->findAll('xpath', "//*[name()='input' or name()='textarea' or name()='select']");
            foreach ($elementsWithErros as $elementWithError) { /* @var $found \Behat\Mink\Element\NodeElement */
                $foundIdsWithErrors[] = $elementWithError->getAttribute('id');
            }
        }
        $untriggeredField = array_diff($fields, $foundIdsWithErrors);
        $unexpectedFields = array_diff($foundIdsWithErrors, $fields);
        
        if ($untriggeredField || $unexpectedFields) {
            $message = "";
            if ($untriggeredField) {
                $message .= " - Form fields not throwing error as expected: \n      " . implode(', ', $untriggeredField) . "\n";
            }
            if ($unexpectedFields) {
                 $message .= " - Form fields unexpectedly throwing errors: \n      " . implode(', ', $unexpectedFields) . "\n";
            }
            
            throw new \RuntimeException($message);
        }
    }
    
    
     /**
     * @Then /^the following fields should have the corresponding values:$/
     */
    public function followingFieldsShouldHaveTheCorrespondingValues(TableNode $fields)
    {
        foreach ($fields->getRowsHash() as $field => $value) {
            $this->assertFieldContains($field, $value);
        }
    }
    
    
    /**
     * @Given I change the report :reportId court order type to :cotName
     */
    public function iChangeTheReportCourtOrderTypeTo($reportId, $cotName)
    {
        $cotNameToId = ['Health & Welfare' => 1, 'Property and Affairs' => 2];
        
        $this->visitBehatLink('report/' . $reportId . '/change-report-cot/' . $cotNameToId[$cotName]);
    }
    
    /**
     * @When I delete all the existing behat users
     */
    public function iDeleteAllTheExistingBehatUsers()
    {
        $this->visitBehatLink('delete-behat-users');
    }
    
    /**
     * @Given I set the report :reportId due
     */
    public function iSetTheReportDue($reportId)
    {
       $endDate = new \DateTime;
       $endDate->modify('-3 days');
       
       $this->visitBehatLink("report/{$reportId}/change-report-end-date/" . $endDate->format('Y-m-d'));
       $this->visit("/");
    }
    
    /**
     * @Given I set the report :reportId not due
     */
    public function iSetTheReportNotDue($reportId)
    {
       $endDate = new \DateTime;
       $endDate->modify('+3 days');
       
       $this->visitBehatLink("report/{$reportId}/change-report-end-date/" . $endDate->format('Y-m-d'));
       $this->visit("/");
    }
    
    /**
     * @Given I reset the behat data
     */
    public function iResetTheBehatData()
    {
        $this->visitBehatLink("delete-behat-data");
    }
    
    /**
     * @Given I visit the behat link :link
     */
    
    public function visitBehatLink($link)
    {
       $secret = md5('behat-dd-' . $this->getSymfonyParam('secret'));
       
       $this->visit("behat/{$secret}/{$link}");
    }
    
    /**
     * @Then I expire the session
     */
    public function iExpireTheSession()
    {
        $this->getSession()->setCookie($this->sessionName, null);
    }   
    
    
    /**
     * @Then the response should have the :arg1 header containing :arg2
     */
    public function theResponseShouldHaveTheHeaderContaining($header, $value)
    {
        $headers = $this->getSession()->getDriver()->getResponseHeaders();
        if (empty($headers[$header][0])) {
            throw new \Exception("Header '{$header}' not found.");
        }
        if (strpos($headers[$header][0], $value) === false) {
            throw new \Exception("Header '{$header}' has value '{$headers[$header][0]}' that does not contains '{$value}'");
        }
        
    }
    
    /**
     * @Given I am on the first report overview page
     * @Given I go to the first report overview page
     */
    public function iAmOnTheReport1Page()
    {
        $this->clickOnBehatLink('client-home');
        $this->clickOnBehatLink('report-n1');
    }
    
    /**
     * @Given I am on the accounts page of the first report
     * @Given I go to the accounts page of the first report
     */
    public function iAmOnTheReport1AccountsPage()
    {
        $this->iAmOnTheReport1Page();
        $this->clickLink('tab-accounts');
    }
    
    /**
     * @Given I am on the account :accountNumber page of the first report
     * @Given I go to the account :accountNumber page of the first report
     */
    public function iAmOnTheReport1AccountPageByAccNumber($accountNumber)
    {
        $this->iAmOnTheReport1AccountsPage();
        $this->clickOnBehatLink('account-' . $accountNumber);
    }
    
    /**
     * @Then the URL :url should not be accessible
     */
    public function theUrlShouldNotBeAccessible($url)
    {
        $previousUrl = $this->getSession()->getCurrentUrl();
        $this->visit($url);
        $this->assertResponseStatus(500);
        $this->visit($previousUrl);
    }
    
    /**
     * @Given The response header :header should contain :value
     */
    public function theResponseHeaderShouldContain($header, $value)
    {
        $responseHeaders = $this->getSession()->getDriver()->getResponseHeaders();
        
        if (!isset($responseHeaders[$header])) {
             throw new \Exception("Header $header not found in response. Headers found: " . implode(', ', array_keys($responseHeaders)));
        }
        
        // search in 
        $found = false;
        foreach ((array)$responseHeaders[$header] as $currentValue) {
            if (strpos($currentValue, $value) !== false) {
                $found = true;
            }
        }
        if (!$found) {
            throw new \Exception("Header $header not found in response. Values: " . implode(', ', $responseHeaders[$header]));
        }
    }
    
    /**
     * @Given I change the report :reportId submitted to :value
     */
    public function iChangeTheReportToNotSubmitted($reportId, $value)
    {
        $this->visitBehatLink('report/' . $reportId . '/set-sumbmitted/' . $value);
    }
    
    /**
     * @Then the last audit log entry should contain:
     */
    public function theLastAuditLogEntryShouldContain(TableNode $fields)
    {
        $this->visitBehatLink('view-audit-log');
        
        foreach ($fields->getRowsHash() as $field => $value) {
            $this->iShouldSeeInTheRegion($value, 'entry-1');
        }
        
    }
    
    /**
     * @Then the :text link url should contain ":expectedLink"
     */
    public function linkWithTextContains($text, $expectedLink)
    {
    
        $linksElementsFound = $this->getSession()->getPage()->find('xpath', '//a[text()="' . $text . '"]');
        $count = count($linksElementsFound);
        
        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException("Element not found");
        }
        
        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException("Returned multiple elements");
        }
    
        $href = $linksElementsFound->getAttribute('href');
        
        if (strpos($href, $expectedLink) === FALSE) {
            throw new \Exception("Link: $href does not contain $expectedLink");
        }

    }
    
    /**
     * @Given I am on the feedback page
     * @Given I goto the feedback page
     */
    public function feedbackPage()
    {
        $this->visit('/feedback');
    }
    
}