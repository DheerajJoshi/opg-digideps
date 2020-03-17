Feature: PA cannot access other's PA's reports and clients
# team1 = team with client 2100010
# team2 = team with client 2000003

  Scenario: PA reload status from the point where team1 has been fully added
    Given I load the application status from "team-users-complete"

  Scenario: Assert team1 can only access its reports
    # Named PA
    Given I am logged in as "behat-pa1@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-02100010" region
    Then the response status code should be 200
    And the URL should match "report/\d+/overview"
    And I save the current URL as "report-for-client-02100010.url"
    But I should not see the "client-02000003" region
    # Admin
    Given I am logged in as "behat-pa1-admin@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-02100010" region
    Then the response status code should be 200
    And the current URL should match with the URL previously saved as "report-for-client-02100010.url"
    But I should not see the "client-02000003" region
    # team member
    Given I am logged in as "behat-pa1-team-member@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-02100010" region
    Then the response status code should be 200
    And the current URL should match with the URL previously saved as "report-for-client-02100010.url"
    But I should not see the "client-02000003" region

  Scenario: team2 can access its client but not team1's data
    # can access team2 reports
    Given I am logged in as "behat-pa2@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-02200001" region
    Then the response status code should be 200
    And the URL should match "report/\d+/overview"
    # cannot access team1 reports
    But I should not see the "client-02100010" region
    When I go to the URL previously saved as "report-for-client-02100010.url"
    Then the response status code should be 404

  Scenario: PA user cannot edit client
    Given I am logged in as "behat-pa1@publicguardian.gov.uk" with password "Abcd1234"
    Then the URL "/deputyship-details" should be forbidden
    And the URL "/deputyship-details/your-client" should be forbidden
    And the URL "/deputyship-details/your-client/edit" should be forbidden
    And the URL "/deputyship-details/your-details" should be forbidden
    And the URL "/deputyship-details/your-details/edit" should be forbidden
    And the URL "/deputyship-details/your-details/change-password" should be forbidden

  Scenario: Submitted reports cannot be viewed (overview page) or edited
    # load "pre-submission" status and save links
    Given I load the application status from "pa-report-completed"
    And I am logged in as "behat-pa1@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-02100014" region
    And I save the current URL as "client-02100014-report-overview"
    And I click on "edit-report-period"
    Then the response status code should be 200
    # load "after submission" status and re-check the same links
    And I save the current URL as "client-02100014-report-completed"
    When I load the application status from "pa-report-submitted"
    When I go to the URL previously saved as "client-02100014-report-overview"
    Then the response status code should be 404
    When I go to the URL previously saved as "client-02100014-report-completed"
    Then the response status code should be 404

  Scenario: PA_ADMIN logs in, edits own account and removes admin privilege should be logged out
    Given I load the application status from "team-users-complete"
    And I am logged in as "behat-pa1@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "org-settings, user-accounts"
    When I click on "edit" in the "team-user-behat-pa1-adminpublicguardiangovuk" region
    And I fill in the following:
      | team_member_account_roleName_1 | ROLE_PA_TEAM_MEMBER                             |
    And I press "team_member_account_save"
    Then the form should be valid
    And the response status code should be 200
    And I go to "/logout"

  Scenario: PA_ADMIN logs in, edits own account keeps admin privilege should remain logged in
    Given I load the application status from "team-users-complete"
    And I am logged in as "behat-pa1@publicguardian.gov.uk" with password "Abcd1234"
    When I click on "org-settings, user-accounts"
    When I click on "edit" in the "team-user-behat-pa1-adminpublicguardiangovuk" region
    And I fill in the following:
      | team_member_account_firstname  | edit                                             |
    And I press "team_member_account_save"
    Then the form should be valid
    And the response status code should be 200
    And I go to "/org/team"

  Scenario: CSV org-upload
    Given I am logged in to admin as "admin@publicguardian.gov.uk" with password "Abcd1234"
    # upload PA users
    When I go to admin page "/admin/org-csv-upload"
    And I attach the file "behat-pa-orgs.csv" to "admin_upload_file"
    And I press "admin_upload_upload"
    Then the form should be valid

  Scenario: Admin activates PA Org 1 deputy
    Given I am logged in to admin as "admin@publicguardian.gov.uk" with password "Abcd1234"
    And emails are sent from "admin" area
    And the following users exist:
      | ndr | deputyType | firstName | lastName | email | postCode | activated |
      | disabled | PA | Org1 Case | Worker | behat-pa-org1@pa-org1.gov.uk | SW1 | false |
    # simulate existing deputies with clients by adding entry to deputy_case table
    And I add the client with case number "40000041" to be deputised by email "behat-pa-org1@pa-org1.gov.uk"
    # activate PA Org 1 user
    When I am on admin page "/admin"
    And I click on "send-activation-email" in the "user-behat-pa-org1pa-org1govuk" region
    Then the response status code should be 200
    And the last email containing a link matching "/user/activate/" should have been sent to "behat-pa-org1@pa-org1.gov.uk"
    And I open the "/user/activate/" link from the email
    # terms
    When I check "agree_terms_agreeTermsUse"
    And I press "agree_terms_save"
    Then the form should be valid
    # password step
    When I fill in the password fields with "Abcd1234"
    And I check "set_password_showTermsAndConditions"
    And I click on "save"
    Then the form should be valid
    When I fill in the following:
      | user_details_jobTitle   | Case worker      |
      | user_details_phoneMain  | 40000000001 |
    And I press "user_details_save"
    Then the form should be valid

  Scenario: Admin activates PA Org 2 deputy
    Given I am logged in to admin as "admin@publicguardian.gov.uk" with password "Abcd1234"
    And emails are sent from "admin" area
    And the following users exist:
      | ndr | deputyType | firstName | lastName | email | postCode | activated |
      | disabled | PA | Org2 Case | Worker | behat-pa-org2@pa-org2.gov.uk | SW1 | false |
    # simulate existing deputies with clients by adding entry to deputy_case table
    And I add the client with case number "40000042" to be deputised by email "behat-pa-org2@pa-org2.gov.uk"
   # activate PA Org 1 user
    When I am on admin page "/admin"
    And I click on "send-activation-email" in the "user-behat-pa-org2pa-org2govuk" region
    Then the response status code should be 200
    And the last email containing a link matching "/user/activate/" should have been sent to "behat-pa-org2@pa-org2.gov.uk"
    And I open the "/user/activate/" link from the email
   # terms
    When I check "agree_terms_agreeTermsUse"
    And I press "agree_terms_save"
    Then the form should be valid
   # password step
    When I fill in the password fields with "Abcd1234"
    And I check "set_password_showTermsAndConditions"
    And I click on "save"
    Then the form should be valid
    When I fill in the following:
      | user_details_jobTitle   | Case worker      |
      | user_details_phoneMain  | 40000000002 |
    And I press "user_details_save"
    Then the form should be valid

  Scenario: PA Org 1 can access own reports and clients
    Given I am logged in as "behat-pa-org1@pa-org1.gov.uk" with password "Abcd1234"
    # access report and save for future feature tests
    Then I click on "pa-report-open" in the "client-40000041" region
    And I save the report as "40000041-report"
    And I click on "client-edit"
    And the response status code should be 200
    And I save the current URL as "client-40000041-edit"
    Then I go to "/logout"

  Scenario: PA Org 2 can access own reports and clients
    Given I am logged in as "behat-pa-org2@pa-org2.gov.uk" with password "Abcd1234"
    # access report and save for future feature tests
    Then I click on "pa-report-open" in the "client-40000042" region
    And I save the report as "40000042-report"
    And I click on "client-edit"
    And the response status code should be 200
    And I save the current URL as "client-40000042-edit"
    Then I go to "/logout"

  Scenario: PA Org 1 user logs in and should only see their clients and reports (from the existing team structure)
    Given I am logged in as "behat-pa-org1@pa-org1.gov.uk" with password "Abcd1234"
    # check I'm in the dashboard and I see only my own client
    And I should see the "client-40000041" region
    And I should not see the "client-40000042" region
    Then I go to the report URL "overview" for "40000042-report"
    And the response status code should be 404
    Then I go to the URL previously saved as "client-40000042-edit"
    And the response status code should be 404

  Scenario: User in an active organisation attempting to access clients inside and outside of the organisation
    Given the organisation "pa-org1.gov.uk" is active
    And "behat-pa-org1@pa-org1.gov.uk" has been added to the "pa-org1.gov.uk" organisation
    When I am logged in as "behat-pa-org1@pa-org1.gov.uk" with password "Abcd1234"
    Then I should see the "client-40000041" region
    And I should not see the "client-40000042" region
    Then I go to the report URL "overview" for "40000041-report"
    And the response status code should be 200
    Then I go to the report URL "overview" for "40000042-report"
    And the response status code should be 404

  Scenario: User not in an organisation attempting to access their client who is in an active organisation
    Given the organisation "pa-org1.gov.uk" is active
    And "behat-pa-org1@pa-org1.gov.uk" has been removed from their organisation
    When I am logged in as "behat-pa-org1@pa-org1.gov.uk" with password "Abcd1234"
    And I go to the report URL "overview" for "40000041-report"
    And the response status code should be 404

  Scenario: User not in an organisation attempting to access their client who is in an inactive organisation
    Given the organisation "pa-org1.gov.uk" is inactive
    And "behat-pa-org1@pa-org1.gov.uk" has been removed from their organisation
    When I am logged in as "behat-pa-org1@pa-org1.gov.uk" with password "Abcd1234"
    And I go to the report URL "overview" for "40000041-report"
    And the response status code should be 200
