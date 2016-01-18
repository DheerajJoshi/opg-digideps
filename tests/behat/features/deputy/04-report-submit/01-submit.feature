Feature: deputy / report / submit
    
    @deputy
    Scenario: report declaration page
        Given I set the report 1 end date to 3 days ago
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I click on "client-home"
        Then I should not see the "download-2016-report" link
        When I click on "report-2016"    
        And I follow "edit-report_add_further_info"
        #And I fill in "report_add_info_furtherInformation" with "test"
        Then I press "report_add_info_saveAndContinue"
        Then the URL should match "/report/\d+/declaration"
        And I save the page as "report-submit-declaration"
        
    @deputy
    Scenario: report submission
        Given I reset the email log
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I save the application status into "report-submit-pre"
        # assert after login I'm redirected to report page
        Then the URL should match "/report/\d+/overview"
        # assert I cannot access the submitted page directly
        And the URL "/report/1/submitted" should not be accessible
        # assert I cannot access the submit page from declaration page
        When I go to "/report/1/declaration"
        Then the URL "/report/1/submitted" should not be accessible
        And I go to the "2016" report overview page
        # submit without ticking "agree"
        When I go to "/report/1/declaration"
        And I press "report_declaration_save"
        # tick agree and submit
        When I check "report_declaration_agree"
        When I fill in the following:
            | report_declaration_allAgreed_0 | 1 |
        And I press "report_declaration_save"
        And the form should be valid
        And the response status code should be 200
        And the URL should match "/report/\d+/submitted"
        And I save the page as "report-submit-submitted"
        # assert report display page is not broken
        When I go to "/report/1/display"
        #Then the response status code should be 200
        And I save the page as "report-submit-display"
        And the last email containing a link matching "/report/[0-9]+/overview" should have been sent to "behat-user@publicguardian.gsi.gov.uk"
        And the second_last email should have been sent to "behat-digideps@digital.justice.gov.uk"
        And the second_last email should contain a PDF of at least 40 kb
        And I save the application status into "report-submit-post"
    

    @deputy
    Scenario: submit feedback after report
        Given I reset the email log
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I go to "/report/1/submitted"
        And I press "feedback_report_save"
        Then the form should be invalid
        And fill in "feedback_report_satisfactionLevel_2" with "Neither satisfied or dissatisfied"
        And I press "feedback_report_save"
        Then the form should be valid
        And I should be on "/report/1/submit_feedback"
        And the last email should contain "Neither satisfied or dissatisfied"


    @deputy
    Scenario: assert 2nd year report has been created
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I click on "client-home"
        And I edit lastest active report
        When I click on "client-home"
        And I click on "report-2016-to-2017"
        And I save the page as "report-property-affairs-homepage"
        Then I should see a "#edit-contacts" element
        And I should see a "#edit-decisions" element
        And I should see a "#edit-accounts" element
        And I should see a "#edit-assets" element
        When I follow "edit-accounts"
        And I click on "account-0876"
        # check no data was previously saved
        Then the following fields should have the corresponding values:
            | account_bank  | HSBC main account |
            | account_openingBalance  | 1,155.00 |
        When I click on "account-moneyin"
        Then I should see an "#transactions_transactionsIn_0_amount" element
        When I click on "account-moneyout"
        Then I should see an "#transactions_transactionsOut_0_amount" element
        

    @deputy
    Scenario: assert report is not editable after submission
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I click on "client-home"
        # assert I'm on the client homepage (cannot redirect to report overview as not acessible anymore)
        Then I should be on "/client/show"
        Then I should not see the "edit-report-period-2016-report" link
        And I should not see the "report-2016" link
        And I should see the "report-2016-submitted-on" region
        And the URL "/report/1/overview" should not be accessible
        And the URL "/report/1/contacts" should not be accessible
        And the URL "/report/1/decisions" should not be accessible
        And the URL "/report/1/accounts" should not be accessible
        And the URL "/report/1/accounts/banks/1/edit" should not be accessible
        And the URL "/report/1/accounts/banks/1/delete" should not be accessible
        And the URL "/report/1/assets" should not be accessible
        
    @deputy
    Scenario: report download
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I click on "client-home"
        # download report from confirmation page
        When I go to "/report/1/submitted"
        When I click on "download-report"
        And the response should contain "12345ABC"
        And the response should contain "Peter White"
        # download report from client page
        #When I go to the homepage
        When I click on "client-home"
        And I click on "download-2016-report"
        And the response should contain "12345ABC"
        And the response should contain "Peter White"
        # test go back link
        When I click on "back-to-client"
        Then I should be on "/client/show"


    @deputy
    Scenario: change report to "not submitted" 
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I change the report "1" submitted to "false"


    @deputy
    Scenario: Must agree
        Given I reset the email log
        When I load the application status from "report-submit-pre"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I go to "/report/1/declaration"
        When I fill in the following:
            | report_declaration_allAgreed_0 | 1 |
        And I press "report_declaration_save"
        Then the following fields should have an error:
            | report_declaration_agree |
        Then I check "report_declaration_agree"
        When I fill in the following:
            | report_declaration_allAgreed_0 | 1 |
        And I press "report_declaration_save"
        Then the URL should match "/report/\d+/submitted"
        Then I load the application status from "report-submit-post"

        
    @deputy
    Scenario: Must all agree
        Given I reset the email log
        When I load the application status from "report-submit-pre"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I go to "/report/1/declaration"
        Then I check "report_declaration_agree"
        And I press "report_declaration_save"
        Then the following fields should have an error:
            | report_declaration_allAgreed_0 |
            | report_declaration_allAgreed_1 |
            | report_declaration_reasonNotAllAgreed |
        When I load the application status from "report-submit-post"

        
    @deputy
    Scenario: If not all agree, need reason
        Given I reset the email log
        When I load the application status from "report-submit-pre"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I go to "/report/1/declaration"
        Then I check "report_declaration_agree"
        When I fill in the following:
            | report_declaration_allAgreed_1 | 0 |
        And I press "report_declaration_save"
        Then the following fields should have an error:
            | report_declaration_reasonNotAllAgreed |
        When I load the application status from "report-submit-post"


    @deputy
    Scenario: Submit with reason we dont all agree
        Given I reset the email log
        When I load the application status from "report-submit-pre"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I go to "/report/1/declaration"
        Then I check "report_declaration_agree"
        When I fill in the following:
            | report_declaration_allAgreed_1 | 0 |
        Then I fill in the following:
            | report_declaration_reasonNotAllAgreed | test |
        And I press "report_declaration_save"
        Then the URL should match "/report/\d+/submitted"
        When I load the application status from "report-submit-post"

