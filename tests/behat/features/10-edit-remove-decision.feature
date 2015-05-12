Feature: edit/remove decision

    @deputy
    Scenario: edit remove decision
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I should be on "client/show"
        And I click on "report-n1"
        And I am on the first report overview page
        And I follow "tab-decisions"
        And the URL should match "/report/\d+/decisions"
        And I click on "decision-n1"
        Then the following fields should have the corresponding values:
            | decision_description | 2 beds |
            | decision_decisionDate_day | 01 |
            | decision_decisionDate_month | 01 |
            | decision_decisionDate_year | 2015 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 90% |
        And I click on "cancel-edit"
        And the URL should match "/report/\d+/decisions"
        And I click on "decision-n1"
        When I fill in the following:
            | decision_description |  |
            | decision_decisionDate_day |  |
            | decision_decisionDate_month |  |
            | decision_decisionDate_year |  |
            | decision_clientInvolvedDetails |  |
        And I press "decision_save"
        Then the following fields should have an error:
            | decision_description |
            | decision_decisionDate_day |
            | decision_decisionDate_month |
            | decision_decisionDate_year |
            | decision_clientInvolvedDetails |
        When I fill in the following:
            | decision_description | 5 beds |
            | decision_decisionDate_day | 2 |
            | decision_decisionDate_month | 2 |
            | decision_decisionDate_year | 2015 |
            | decision_clientInvolvedBoolean_0 | 1 |
            | decision_clientInvolvedDetails | the client was able to decide at 100% |
        And I press "decision_save"
        Then I should see "5 beds" in the "list-decisions" region
        And I should see "the client was able to decide at 100%" in the "list-decisions" region
        And I click on "decision-n1"
        And I click on "delete-confirm"
        And the URL should match "/report/\d+/decisions/delete-confirm/\d+#delete-confirm"
        And I click on "delete-confirm-cancel"
        And the URL should match "/report/\d+/decisions/edit/\d+#edit-\d+"
        And I click on "delete-confirm"
        And I click on "delete"
        And the URL should match "/report/\d+/decisions"
        Then I should not see "the client was able to decide at 100%" in the "list-decisions" region


