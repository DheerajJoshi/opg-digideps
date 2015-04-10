Feature: report
    
    @deputy
    Scenario: test tabs for "Health & Welfare" report
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I save the page as "report-health-welfare-homepage"
        When I am on the homepage
        Then I should see a "#tab-contacts" element
        And I should see a "#tab-decisions" element
        But I should not see a "#tab-accounts" element
        And I should not see a "#tab-assets" element

    @deputy
    Scenario: change report type to "Property and Affairs"
        Given I change the report "1" court order type to "Property and Affairs"

    @deputy
    Scenario: test tabs for "Property and Affairs" report
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I am on the homepage
        And I save the page as "report-property-affairs-homepage"
        Then I should see a "#tab-contacts" element
        And I should see a "#tab-decisions" element
        And I should see a "#tab-accounts" element
        And I should see a "#tab-assets" element

    @deputy
    Scenario: Check report notification and submission warnings
        # set report due
        Given I modify the report 1 end date to today "-3 days"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I should see the "tab-contacts-warning" region
        Then I should see the "tab-decisions-warning" region
        Then I should see the "tab-accounts-warning" region
        Then I should see the "tab-assets-warning" region
        # disabled element are not visible from behat
        And I should not see a "report_submit_submitReport" element
        # set back report not to be due
        And I modify the report 1 end date to today "+3 days"

    @deputy
    Scenario: add contact
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I follow "tab-contacts"
        And I save the page as "report-contact-empty"
        # wrong form
        And I submit the form
        And I save the page as "report-contact-add-error"
        Then the following fields should have an error:
            | contact_contactName |
            | contact_relationship |
            | contact_explanation |
            | contact_address |
            | contact_postcode |
        # right values
        And I fill in the following:
            | contact_contactName | Andy White |
            | contact_relationship | brother  |
            | contact_explanation | no explanation |
            | contact_address | 45 Noth Road |
            | contact_address2 | Inslington |
            | contact_county | London |
            | contact_postcode | N2 5JF |
            | contact_country | GB |
        And I submit the form
        And I save the page as "report-contact-list"
        Then the response status code should be 200
        And the form should not contain an error
        And the URL should match "/report/\d+/contacts"
        And I should see "Andy White" in the "list-contacts" region


    @deputy
    Scenario: add decision
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I follow "tab-decisions"
        And I save the page as "report-decision-empty"
        # form errors
        When I submit the form
        And I save the page as "report-decision-add-error"
        Then the following fields should have an error:
            #| decision_title |
            | decision_description |
            | decision_clientInvolvedDetails |
            | decision_decisionDate_day |
            | decision_decisionDate_month |
            | decision_decisionDate_year |
            | decision_clientInvolvedBoolean_0 |
            | decision_clientInvolvedBoolean_1 |
        # wrong date
        And I fill in the following:
            #| decision_title | Bought house in Sw18 |
            | decision_description | 2 beds |
            | decision_decisionDate_day | 30 |
            | decision_decisionDate_month | 02 |
            | decision_decisionDate_year | 9999 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 90% |
        And I submit the form
        And the form should contain an error
        # date not in report range
        And I fill in the following:
            #| decision_title | Bought house in Sw18 |
            | decision_description | 2 beds |
            | decision_decisionDate_day | 30 |
            | decision_decisionDate_month | 12 |
            | decision_decisionDate_year | 2002 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 90% |
        And I submit the form
        And the form should contain an error
        # missing involvement details
        And I fill in the following:
            #| decision_title | Bought house in Sw18 |
            | decision_description | 2 beds |
            | decision_decisionDate_day | 30 |
            | decision_decisionDate_month | 12 |
            | decision_decisionDate_year | 2015 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails |  |
        And I submit the form
        And the form should contain an error
        # add decision on 1/1/2015
        And I fill in the following:
            #| decision_title | Bought house in Sw18 |
            | decision_description | 2 beds |
            | decision_decisionDate_day | 1 |
            | decision_decisionDate_month | 1 |
            | decision_decisionDate_year | 2015 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 90% |
        And I submit the form
        And I save the page as "report-decision-list"
        Then the response status code should be 200
        And the form should not contain an error
        When I click on "add-a-decision"
        # add another decision on 31/12/2015
         And I fill in the following:
            #| decision_title | Sold house in Sw18 |
            | decision_description | 3 beds |
            | decision_decisionDate_day | 2 |
            | decision_decisionDate_month | 1 |
            | decision_decisionDate_year | 2015 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 85% |
        And I submit the form
        And the form should not contain an error
        And I should see "2 beds" in the "list-decisions" region
        And I should see "3 beds" in the "list-decisions" region
        
    @deputy
    Scenario: add asset
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I follow "tab-assets"
        And I save the page as "report-assets-empty"
        # wrong form
        And I press "asset_save"
        And I save the page as "report-assets-add-error-empty"
        Then the following fields should have an error:
            | asset_title |
            | asset_value |
            | asset_description |
        # invalid date
        When I fill in the following:
            | asset_title       | Vehicles | 
            | asset_value       | 13000.00 | 
            | asset_description | Alfa Romeo 156 1.9 JTD | 
            | asset_valuationDate_day | 99 | 
            | asset_valuationDate_month |  | 
            | asset_valuationDate_year | 2015 | 
        And I press "asset_save"
        And I save the page as "report-assets-add-error-date"
        Then the following fields should have an error:
            | asset_valuationDate_day |
            | asset_valuationDate_month |
            | asset_valuationDate_year |
        # 1st asset (empty date)
        When I fill in the following:
            | asset_title       | Property | 
            | asset_value       | 250000.00 | 
            | asset_description | 2 beds flat in HA2 | 
            | asset_valuationDate_day |  | 
            | asset_valuationDate_month |  | 
            | asset_valuationDate_year |  | 
        And I press "asset_save"
        And I save the page as "report-assets-list-one"
        Then the response status code should be 200
        And the form should not contain an error
        And I should see "2 beds flat in HA2" in the "list-assets" region
        And I should see "£250,000.00" in the "list-assets" region
        When I click on "add-an-asset"
        # 2nd asset (with date)
        And I fill in the following:
            | asset_title       | Vehicles | 
            | asset_value       | 13000.00 | 
            | asset_description | Alfa Romeo 156 1.9 JTD | 
            | asset_valuationDate_day | 10 | 
            | asset_valuationDate_month | 11 | 
            | asset_valuationDate_year | 2015 | 
        And I press "asset_save"
        And I save the page as "report-assets-list-two"
        Then I should see "Alfa Romeo 156 1.9 JTD" in the "list-assets" region
        And I should see "£13,000.00" in the "list-assets" region


    @deputy
    Scenario: add account
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I am on the homepage
        And I follow "tab-accounts"
        And I save the page as "report-account-empty"
        # wrong form
        And I press "account_save"
        And I save the page as "report-account-add-error"
        Then the following fields should have an error:
            | account_bank |
            | account_accountNumber_part_1 |
            | account_accountNumber_part_2 |
            | account_accountNumber_part_3 |
            | account_accountNumber_part_4 |
            | account_sortCode_sort_code_part_1 |
            | account_sortCode_sort_code_part_2 |
            | account_sortCode_sort_code_part_3 |
            | account_openingDate_day |
            | account_openingDate_month |
            | account_openingDate_year |
            | account_openingBalance |
        # right values
        And I fill in the following:
            | account_bank    | HSBC main account | 
            | account_accountNumber_part_1 | 1 | 
            | account_accountNumber_part_2 | 2 | 
            | account_accountNumber_part_3 | 3 | 
            | account_accountNumber_part_4 | 4 | 
            | account_sortCode_sort_code_part_1 | 12 |
            | account_sortCode_sort_code_part_2 | 34 |
            | account_sortCode_sort_code_part_3 | 56 |
            | account_openingDate_day   | 01 |
            | account_openingDate_month | 01 |
            | account_openingDate_year  | 2015 |
            | account_openingBalance  | 1,150.00 |
        And I press "account_save"
        And I save the page as "report-account-list"
        Then the response status code should be 200
        And the form should not contain an error
        And the URL should match "/report/\d+/account/\d+"
        When I follow "tab-accounts"
        And I should see "HSBC main account" in the "list-accounts" region
        And I should see "1234" in the "list-accounts" region
        And I should see "£1,150.00" in the "list-accounts" region
    

    @deputy
    Scenario: add account transactions
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I follow "tab-accounts"
        And I click on "account-n1"
        And I click on "moneyIn-tab"
        And I click on "moneyOut-tab"
        # check no data was previously saved
        Then the following fields should have the corresponding values:
            | transactions_moneyIn_0_amount        |  | 
            | transactions_moneyIn_15_amount       |  | 
            | transactions_moneyIn_15_moreDetails  |  | 
            | transactions_moneyOut_0_amount       |  | 
            | transactions_moneyOut_11_amount      |  | 
            | transactions_moneyOut_11_moreDetails |  | 
        And I save the page as "report-account-transactions-empty"
        # wrong values (wrong amount types and amount without explanation)
        When I fill in the following:
            | transactions_moneyIn_0_amount        | in | 
            | transactions_moneyIn_1_amount        | 25,0000 | 
            | transactions_moneyIn_2_amount        | 25.25.25 | 
            | transactions_moneyIn_3_amount        | 250.250,12 | 
            | transactions_moneyOut_11_amount      | 250.12 | 
            | transactions_moneyOut_11_moreDetails |  | 
        And I press "transactions_saveMoneyIn"
        Then the following fields should have an error:
            | transactions_moneyIn_0_amount  |
            | transactions_moneyIn_1_amount  |
            | transactions_moneyIn_2_amount  |
            | transactions_moneyIn_3_amount  |
            | transactions_moneyOut_11_id |
            | transactions_moneyOut_11_type |
            | transactions_moneyOut_11_amount |
            | transactions_moneyOut_11_moreDetails |
        And I save the page as "report-account-transactions-errors"    
        # right values
        When I fill in the following:
            | transactions_moneyIn_0_amount       | 1,250 | 
            | transactions_moneyIn_1_amount       |  | 
            | transactions_moneyIn_2_amount       |  | 
            | transactions_moneyIn_3_amount       |  | 
            | transactions_moneyIn_15_amount      | 2,000.0 | 
            | transactions_moneyIn_15_moreDetails | more-details-in-15  |
            | transactions_moneyOut_0_amount       | 02500 | 
            | transactions_moneyOut_11_amount      | 5000.501 | 
            | transactions_moneyOut_11_moreDetails | more-details-out-11 | 
        And I press "transactions_saveMoneyIn"
        Then the form should not contain an error
        # assert value saved
        And the following fields should have the corresponding values:
            | transactions_moneyIn_0_amount       | 1,250.00 | 
            | transactions_moneyIn_15_amount      | 2,000.00 | 
            | transactions_moneyIn_15_moreDetails | more-details-in-15  |
            | transactions_moneyOut_0_amount       | 2,500.00 | 
            | transactions_moneyOut_11_amount      | 5,000.50 | 
            | transactions_moneyOut_11_moreDetails | more-details-out-11 | 
        And I should see "£3,250.00" in the "moneyIn-total" region
        And I should see "£7,500.50" in the "moneyOut-total" region
        And I should see "£-3,100.50" in the "money-totals" region
        And I save the page as "report-account-transactions-data-saved"


    @deputy
    Scenario: add closing balance to account
        Given I modify the report 1 end date to today "+3 days"
        And I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        When I follow "tab-accounts"
        Then I should not see the "account-1-add-closing-balance" link
        When I modify the report 1 end date to today "-3 days"
        And I go to the homepage
        When I follow "tab-accounts"
        Then I should see the "account-n1-warning" region
        And I click on "account-n1"
        Then the following fields should have the corresponding values:
            | accountBalance_closingDate_day   | | 
            | accountBalance_closingDate_month | | 
            | accountBalance_closingDate_year  | | 
            | accountBalance_closingBalance    | | 
        # wrong values
        When I fill in the following:
            | accountBalance_closingDate_day   | 99 | 
            | accountBalance_closingDate_month | 99 | 
            | accountBalance_closingDate_year  | 1 | 
            | accountBalance_closingBalance    | invalid value | 
        And I press "accountBalance_save"
        Then the following fields should have an error:
            | accountBalance_closingDate_day   |
            | accountBalance_closingDate_month |
            | accountBalance_closingDate_year  |
            | accountBalance_closingBalance    |
        # right values  
        When I fill in the following:
            | accountBalance_closingDate_day   | 30 | 
            | accountBalance_closingDate_month | 12 | 
            | accountBalance_closingDate_year  | 2015 | 
            | accountBalance_closingBalance    | 3,105.50 | 
        And I press "accountBalance_save"
        Then I should not see the "account-closing-balance-form" region
        # assert transactions are not changed due to the form in the same page
        And I should see "£-3,100.50" in the "money-totals" region
        When I follow "tab-accounts"
        Then I should see "3,105.50" in the "account-1-closing-balance" region
        And I should see "30/12/2015" in the "account-1-closing-date" region


    @deputy
    Scenario: submit report
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        # check there are no notifications
        Then I should not see the "tab-contacts-warning" region
        Then I should not see the "tab-decisions-warning" region
        Then I should not see the "tab-accounts-warning" region
        Then I should not see the "tab-assets-warning" region
        # set report due
        Given I modify the report 1 end date to today "-3 days"
        And I go to the homepage
        Then I should not see a "tab-contact-notification" element
        # submit without ticking
        When I press "report_submit_submitReport"
        Then the following fields should have an error:
            | report_submit_reviewed_n_checked   |
        # tick and submit
        When I check "report_submit_reviewed_n_checked"
        And I press "report_submit_submitReport"
        Then the URL should match "/report/\d+/declaration"
        # test "go back" link
        And I click on "report-preview-go-back"
        And I check "report_submit_reviewed_n_checked"
        And I press "report_submit_submitReport"
        Then the URL should match "/report/\d+/declaration"
        # preview page: submit without ticking "agree"
        When I submit the form
        Then the following fields should have an error:
            | report_declaration_agree |
        # right values  
        When I check "report_declaration_agree"
        And I submit the form
        And the form should not contain an error
        And the report is submitted
        And the URL should match "/report/\d+/overview"
        #And I should not see "Ready to submit"
        

        
        