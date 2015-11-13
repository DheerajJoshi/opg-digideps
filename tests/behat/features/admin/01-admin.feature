Feature: admin / admin

    Scenario: login and add admin user, check audit log
        Given I reset the email log
        And I am logged in to admin as "admin@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then the last audit log entry should contain:
          | from | admin@publicguardian.gsi.gov.uk |
          | action | login |
        #When I go to "/admin"
        Given I am on admin page "/admin"
        And I create a new "Admin" user "John" "Doe" with email "behat-admin-user@publicguardian.gsi.gov.uk"
        Then I should see "behat-admin-user@publicguardian.gsi.gov.uk" in the "users" region
        Then the response status code should be 200
        And I should see "OPG Administrator" in the "users" region
        And I save the page as "admin-admin-added"
        And the last email containing a link matching "/user/activate/" should have been sent to "behat-admin-user@publicguardian.gsi.gov.uk"
        And the last audit log entry should contain:
          | from | admin@publicguardian.gsi.gov.uk |
          | action | user_add |
          | user_affected | behat-admin-user@publicguardian.gsi.gov.uk |
        #When I go to "/logout"
        Given I am on admin page "/logout"
        Then the last audit log entry should contain:
          | from | admin@publicguardian.gsi.gov.uk |
          | action | logout |


    Scenario: login and add user (admin)
        Given I am not logged into admin
        # assert email link doesn't work on admin area
        When I open the "/user/activate/" link from the email on the "deputy" area
        Then the response status code should be 500
        # follow link as it is
        When I open the "/user/activate/" link from the email
        Then the response status code should be 200
        And I save the page as "admin-step1"
        And the "set_password_email" field should contain "behat-admin-user@publicguardian.gsi.gov.uk"
        # only testing the correct case, as the form is the same for deputy
        When I fill in the following: 
            | set_password_password_first   | Abcd1234 |
            | set_password_password_second  | Abcd1234 |
        And I press "set_password_save"
        Then I should not see the "header errors" region
        And I should be on "/user/details"


    Scenario: change user password on admin area
        Given I am logged in to admin as "behat-admin-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I save the application status into "admin-pasword-change-init"
        And I click on "my-details"
        And I click on "edit-user-details"
        # wrong old password
        When I fill in "user_details_password_current_password" with "this.is.the.wrong.password"
        And I press "user_details_save"
        Then the following fields should have an error:
              | user_details_password_current_password |
              | user_details_password_plain_password_first |
        # invalid new password
        When I fill in the following:
          | user_details_password_current_password | Abcd1234 |
          | user_details_password_plain_password_first | 1 |
          | user_details_password_plain_password_second | 2 |
        And I press "user_details_save"
        Then the following fields should have an error:
              | user_details_password_plain_password_first |      
        # unmatching new passwords
        When I fill in the following:
          | user_details_password_current_password | Abcd1234 |
          | user_details_password_plain_password_first | Abcd1234 |
          | user_details_password_plain_password_second | Abcd12345 |
        And I press "user_details_save"
        Then the following fields should have an error:
              | user_details_password_plain_password_first |
        #empty password
        When I fill in the following:
          | user_details_password_current_password | Abcd1234 |
          | user_details_password_plain_password_first | |
          | user_details_password_plain_password_second | |
        And I press "user_details_save"
        Then the following fields should have an error:
              | user_details_password_plain_password_first |
        # valid new password
        When I fill in the following:
          | user_details_password_current_password | Abcd1234 |
          | user_details_password_plain_password_first | Abcd12345 |
          | user_details_password_plain_password_second | Abcd12345 |
        And I press "user_details_save"
        Then the form should be valid
        And I should be on "/user"
        # restore old password (and assert the current password can be used as old password)
        When I click on "edit-user-details"
        And I fill in the following:
          | user_details_password_current_password | Abcd12345 |
          | user_details_password_plain_password_first | Abcd1234 |
          | user_details_password_plain_password_second | Abcd1234 |
        And I press "user_details_save"
        Then the form should be valid
        And I should be on "/user"   
        And I load the application status from "admin-pasword-change-init"