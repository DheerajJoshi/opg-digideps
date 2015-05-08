Feature: edit/remove an asset


    @deputy
    Scenario: edit asset-remove
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        Then I should be on "client/show"
        And I click on "report-n1"
        And I am on the first report overview page
        And I follow "tab-assets"
        And I click on "asset-n1"
        Then the following fields should have the corresponding values:
            | asset_title | Vehicles |
            | asset_value | 13,000.00 |
            | asset_description | Alfa Romeo 156 1.9 JTD |
            | asset_valuationDate_day | 10 |
            | asset_valuationDate_month | 11 |
            | asset_valuationDate_year | 2015 |
        And I click on "cancel-edit"
        And the URL should match "/report/\d+/assets"
        And I click on "asset-n1"
        When I fill in the following:
            | asset_title | Artwork |
            | asset_value | 10,000.00 |
            | asset_description | I love my artworks |
            | asset_valuationDate_day | 11 |
            | asset_valuationDate_month | 11 |
            | asset_valuationDate_year | 2015 |
       And I press "asset_save"
       Then I should see "I love my artworks" in the "list-assets" region
       And I should see "£10,000.00" in the "list-assets" region
       And I click on "asset-n1"
       And I click on "delete-confirm"
       And the URL should match "/report/\d+/assets/delete-confirm/\d+#asset-delete-confirm"
       And I click on "delete-confirm-cancel"
       And the URL should match "/report/\d+/assets/edit/\d+#asset-edit-\d+"
       And I click on "delete-confirm"
       And I click on "delete"
       And the URL should match "/report/\d+/assets"
       Then I should not see "I love my artworks" in the "list-assets" region
