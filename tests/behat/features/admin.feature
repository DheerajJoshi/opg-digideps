Feature: admin
    
    @cleanMail
    Scenario: login and add user
        Given I am on "/"
        Then the response status code should be 200
        # test wrong credentials
        When I fill in the following: 
            | login_email     | deputyshipservice@publicguardian.gsi.gov.uk |
            | login_password  |  WRONG PASSWORD !! |
        And I click on "login"
        Then I should see the "header errors" region
        # test right credentials
        When I fill in the following:
            | login_email     | deputyshipservice@publicguardian.gsi.gov.uk |
            | login_password  |  test |
        And I click on "login"
        When I go to "/admin"
        And I should not see "behat-user@publicguardian.gsi.gov.uk" in the "users" region
        # assert form error
        When I fill in the following:
            | admin_email | invalidEmail | 
            | admin_firstname | 1 | 
            | admin_lastname | 2 | 
            | admin_role_id | ROLE_LAY_DEPUTY |
        And I press "admin_save"
        Then I should see "is not a valid email"
        And I should see "Your first name must be at least 2 characters long"
        And I should see "Your last name must be at least 2 characters long"
        And I should not see "invalidEmail" in the "users" region 
        # assert form OK
        When I fill in the following:
            | admin_email | behat-user@publicguardian.gsi.gov.uk | 
            | admin_firstname | John | 
            | admin_lastname | Doe | 
            | admin_role_id | ROLE_LAY_DEPUTY |
        And I click on "save"
        Then I should see "behat-user@publicguardian.gsi.gov.uk" in the "users" region
        Then I should see "Lay Deputy" in the "users" region
        And an email with subject "Digideps - activation email" should have been sent to "behat-user@publicguardian.gsi.gov.uk"
        
