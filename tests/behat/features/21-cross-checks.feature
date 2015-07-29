Feature: cross domain (admin and deputy) checks

    @deputy
    Scenario: A deputy cannot login into admin area
        # check deputy can login into deputy site
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then the response status code should be 200
        # check deputy CANNOT login into ADMIN site
        Given I go to "/logout"
        And I am on admin login page
        When I fill in the following:
            | login_email     | behat-user@publicguardian.gsi.gov.ukk |
            | login_password  | Abcd1234 |
        And I click on "login"
        Then I should see the "header errors" region
        And I should be on "/login"


    @deputy
    Scenario: A deputy cannot reset password from the admin area
        # check deputy can recover password from deputy site
        Given I reset the email log
        And I go to "/login"
        When I click on "forgotten-password"
        And I fill in "password_forgotten_email" with "behat-user@publicguardian.gsi.gov.uk"
        And I press "password_forgotten_submit"
        Then the last email should have been sent to "behat-user@publicguardian.gsi.gov.uk"
        # heck deputy CANNOT recover password from admin site
        Given I reset the email log
        And I am on admin login page
        When I click on "forgotten-password"
        And I fill in "password_forgotten_email" with "behat-user@publicguardian.gsi.gov.uk"
        And I press "password_forgotten_submit"
        Then the response status code should be 200
        And no email should have been sent

    @admin
    Scenario: An admin user cannot login into deputy area
        # check admin can login into admin site
        Given I am logged in to admin as "behat-admin-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then the response status code should be 200
        # check admin CANNOT login into DEPUTY site
        Given I go to "/logout"
        And  I am on the login page
        When I fill in the following:
            | login_email     | behat-admin-user@publicguardian.gsi.gov.uk |
            | login_password  | Abcd1234 |
        And I click on "login"
        Then I should see the "header errors" region
        And I should be on "/login"


    @admin
    Scenario: An admin cannot reset password from the deptuy area
        # check admin can recover password from admin site
        Given I reset the email log
        And I am on admin login page
        When I click on "forgotten-password"
        And I fill in "password_forgotten_email" with "behat-admin-user@publicguardian.gsi.gov.uk"
        And I press "password_forgotten_submit"
        Then the last email should have been sent to "behat-admin-user@publicguardian.gsi.gov.uk"
        # check admin CANNOT recover password from DEPUTY site
        Given I reset the email log
        And I am on the login page
        When I click on "forgotten-password"
        And I fill in "password_forgotten_email" with "behat-admin-user@publicguardian.gsi.gov.uk"
        And I press "password_forgotten_submit"
        Then the response status code should be 200
        And no email should have been sent