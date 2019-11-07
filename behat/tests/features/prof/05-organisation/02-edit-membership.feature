Feature: Users can edit members of their organisation

  @prof
  Scenario: Org domains: Users can add existing users to their organisation
    Given the organisation "publicguardian.gov.uk" is active
    And "behat-prof-admin@publicguardian.gov.uk" has been added to the "publicguardian.gov.uk" organisation
    When I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    And I go to "/org/settings/organisation"
    And I follow "Add user"
    When I fill in the following:
      | organisation_member_firstname  | Yvonne                                       |
      | organisation_member_lastname   | Lacasse                                      |
      | organisation_member_email      | behat-prof-team-member@publicguardian.gov.uk |
      | organisation_member_roleName_1 | ROLE_PROF_TEAM_MEMBER                        |
    And I press "Save"
    Then the URL should match "/org/settings/organisation/\d+"
    And I should see "Professional Team Member"
    And I should see "behat-prof-team-member@publicguardian.gov.uk"

  @prof
  Scenario: Org domains: Users can only add new users if they share the same org domain
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    And emails are sent from "deputy" area
    When I go to "/org/settings/organisation"
    And I follow "Add user"
    When I fill in the following:
      | organisation_member_firstname  | Yvonne                                |
      | organisation_member_lastname   | Lacasse                               |
      | organisation_member_email      | john.smith@abc-solicitors.example.com |
      | organisation_member_roleName_1 | ROLE_PROF_TEAM_MEMBER                 |
    And I press "Save"
    Then I should see "Email doesn't match the organisation domain: @publicguardian.gov.uk"
    And the form should be invalid
    When I fill in the following:
      | organisation_member_firstname  | Yvonne                |
      | organisation_member_lastname   | Lacasse               |
      | organisation_member_email      | jo.brown@example.com  |
      | organisation_member_roleName_1 | ROLE_PROF_TEAM_MEMBER |
    And I press "Save"
    Then I should see "Email doesn't match the organisation domain: @publicguardian.gov.uk"
    And the form should be invalid
    When I fill in the following:
      | organisation_member_firstname  | Yvonne                          |
      | organisation_member_lastname   | Lacasse                         |
      | organisation_member_email      | y.lacasse@publicguardian.gov.uk |
      | organisation_member_roleName_0 | ROLE_PROF_ADMIN                 |
    And I press "Save"
    Then the URL should match "/org/settings/organisation/\d+"
    And I should see "Yvonne Lacasse"
    And I should see "y.lacasse@publicguardian.gov.uk"
    And the last email should have been sent to "y.lacasse@publicguardian.gov.uk"
    And the last email should contain "Activate your account"
    And the last email should contain "/user/activate"

  @prof
  Scenario: Public domains: Cannot add users to their organisation
    Given the organisation "jo.brown@example.com" is active
    And "jo.brown@example.com" has been added to the "jo.brown@example.com" organisation
    Given I am logged in as "jo.brown@example.com" with password "Abcd1234"
    When I go to "/org/settings"
    And I follow "User accounts"
    Then I should not see the "Add" link
# assert direct access denied
    When I go to "/org/settings/organisation/add-user"
    Then the response status code should be 500

  @prof
  Scenario: Admin users can edit non-activated users
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    When I go to "/org/settings/organisation"
    And I click on "edit" in the "team-user-ylacassepublicguardiangovuk" region
    Then the "organisation_member_firstname" field should contain "Yvonne"
    And the "organisation_member_lastname" field should contain "Lacasse"
    And the "organisation_member_email" field should contain "y.lacasse@publicguardian.gov.uk"
    When  I fill in "organisation_member_email" with "yvonne.lacasse@publicguardian.gov.uk"
    And I press "Save"
    Then the URL should match "/org/settings/organisation/\d+"
    And I should see "Yvonne Lacasse"
    And I should see "yvonne.lacasse@publicguardian.gov.uk"

  @prof
  Scenario: Admin users can resend activation emails to non-activated users
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    And emails are sent from "deputy" area
    When I go to "/org/settings/organisation"
    And I click on "send-activation-email" in the "team-user-yvonnelacassepublicguardiangovuk" region
    Then the last email should have been sent to "yvonne.lacasse@publicguardian.gov.uk"
    And the last email should contain "Activate your account"
    And the last email should contain "/user/activate"

  @prof
  Scenario: Admin users cannot resend email to activated users
    Given emails are sent from "deputy" area
    When I open the "/user/activate/" link from the email
    And I activate the user with password "Abcd1234"
    And I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    And I go to "/org/settings/organisation"
    Then I should see "Edit" in the "team-user-yvonnelacassepublicguardiangovuk" region
    Then I should not see "Resend activation email" in the "team-user-yvonnelacassepublicguardiangovuk" region

  @prof
  Scenario: Admin users can delete colleagues in their organisation
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    When I go to "/org/settings/organisation"
    And I click on "delete" in the "team-user-yvonnelacassepublicguardiangovuk" region
    Then I should see "Are you sure you want to remove this user from this organisation?"
    And I should see "Yvonne Lacasse"
    And I should see "yvonne.lacasse@publicguardian.gov.uk"
    When I press "Yes, remove user from this organisation"
    Then the URL should match "/org/settings/organisation/\d+"
    And I should not see "Yvonne Lacasse"
    And I should not see "yvonne.lacasse@publicguardian.gov.uk"
