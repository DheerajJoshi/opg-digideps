Feature: admin

    Scenario: login and add user
        Given I am on "/"
        Then the page title should be "Login"
        Then the response status code should be 200
        # test wrong credentials
        When I fill in the following: 
            | email     | deputyshipservice@publicguardian.gsi.gov.uk |
            | password  |  WRONG PASSWORD !! |
        And I click on "login"
        Then I should see "invalid credentials" in the "header errors" region
        # test right credentials
        When I fill in the following:
            | email     | deputyshipservice@publicguardian.gsi.gov.uk |
            | password  |  test |
        And I click on "login"
        Then I should be on "/"
        
        # admin
        When I go to "/admin"
        Then the page title should be "Admin area"
        And I should not see "behat-user@publicguardian.gsi.gov.uk" in the "users" region
        When I fill in "form_email" with "behat-user@publicguardian.gsi.gov.uk"
        And I fill in "form_firstname" with "John"
        And I fill in "form_lastname" with "doe"
        And I press "form_save"
        Then I should see "behat-user@publicguardian.gsi.gov.uk" in the "users" region
        
            
        