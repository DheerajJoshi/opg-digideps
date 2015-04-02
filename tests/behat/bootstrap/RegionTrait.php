<?php

namespace DigidepsBehat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * @method Behat\Mink\WebAssert assertSession
 * @method Behat\Mink\Session getSession
 */
trait RegionTrait
{
    
    /**
     * @Then I should not see the :region :type
     */
    public function iShouldNotSeeTheBehatElement($region, $type)
    {
        $regionCss = self::behatElementToCssSelector($region, $type);
        $linksElementsFound = $this->getSession()->getPage()->findAll('css', $regionCss);
        $count = count($linksElementsFound);
        if ($count > 0) {
            throw new \RuntimeException("$count  $regionCss element(s) found. None expected");
        }
    }
    
    /**
     * @Then I should see the :region :type
     */
    public function iShouldSeeTheBehatElement($region, $type)
    {
        $regionCss = self::behatElementToCssSelector($region, $type);
        $linksElementsFound = $this->getSession()->getPage()->findAll('css', $regionCss);
        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException("Element $regionCss not found");
        }
    }
    
    /**
     * @Then I should see :text in the :region region
     */
    public function iShouldSeeInTheRegion($text, $region)
    {
        $this->assertSession()->elementTextContains('css', self::behatElementToCssSelector($region, 'region'), $text);
    }
    
    /**
     * @Then I should not see :text in the :region region
     */
    public function iShouldNotSeeInTheRegion($text, $region)
    {
        $this->assertSession()->elementTextNotContains('css', self::behatElementToCssSelector($region, 'region'), $text);
    }

    /**
     * @Then I should see each of the following in the :region region:
     */
    public function iShouldSeeTheFollowingInTheRegion($region, PyStringNode $pieces)
    {
        foreach ($pieces->getStrings() as $text) {
            $this->iShouldSeeInTheRegion($text, $region);
        }
    }
    
    
    /**
     * Click on element with attribute [behat-link=:link]
     * 
     * @When I click on :link
     */
    public function clickOnBehatLink($link)
    {
        // find link inside the region
        $linkSelector = self::behatElementToCssSelector($link, 'link');
        $linksElementsFound = $this->getSession()->getPage()->findAll('css', $linkSelector);
        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException("Found more than a $linkSelector element in the page. Interrupted");
        }
        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException("Element $linkSelector not found. Interrupted");
        }
        
        // click on the found link
        $linksElementsFound[0]->click();
    }
    
    
    /**
     * Click on element with attribute [behat-link=:link] inside the element with attribute [behat-region=:region]
     * 
     * @When I click on :link in the :region region
     */
    public function clickLinkInsideElement($link, $region)
    {
        // find region
        $regionSelector = self::behatElementToCssSelector($region, 'region');
        $regionsFound = $this->getSession()->getPage()->findAll('css', $regionSelector);
        if (count($regionsFound) > 1) {
            throw new \RuntimeException("Found more than one $regionSelector");
        }
        if (count($regionsFound) === 0) {
            throw new \RuntimeException("Region $regionSelector not found.");
        }
        
        // find link inside the region
        $linkSelector = self::behatElementToCssSelector($link, 'link');
        $linksElementsFound = $regionsFound[0]->findAll('css', $linkSelector);
        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException("Found more than a $linkSelector element inside $regionSelector . Interrupted");
        }
        if (count($linksElementsFound) === 0) {
           throw new \RuntimeException("Element $linkSelector not found inside $regionSelector . Interrupted");
        }
        
        
        // click on the found link
        $linksElementsFound[0]->click();
    }
    
    
    protected static function behatElementToCssSelector($region, $type)
    {
        return '.behat-'.$type.'-' . preg_replace('/\s+/', '-', $region);
    }
}