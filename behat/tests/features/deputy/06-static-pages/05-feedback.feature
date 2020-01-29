Feature: Generic feedback page

    @deputy
    Scenario: The phase banner provides a link to the feedback page
        Given I am on "/"
        When I follow "feedback"
        Then I should be on "/feedback"

    @deputy
    Scenario: Feedback page accepts as little as a comment
        Given I am on "/feedback"
        And emails are sent from "deputy" area
        When I fill in "feedback_comments" with "Test comment"
        And I press "Send feedback"
        Then the response should contain "Thank you for your feedback"
        And the last email should have been sent to "digideps+noop@digital.justice.gov.uk"
        And the last email sent should have used the general feedback email template

    @deputy
    Scenario: Extra details are included in the email
        Given I am on "/feedback"
        And emails are sent from "deputy" area
        When I fill in "feedback_specificPage_1" with "0"
        And I fill in "feedback_page" with "Title of page"
        And I fill in "feedback_comments" with "A longer comment"
        And I fill in "feedback_name" with "My name"
        And I fill in "feedback_email" with "myemail@emailhost.com"
        And I fill in "feedback_phone" with "054863476384"
        And I fill in "feedback_satisfactionLevel_4" with "1"
        And I press "Send feedback"
        Then the response should contain "Thank you for your feedback"
        And the parameters in the last email sent should include:
            | parameter         | value                 |
            | satisfactionLevel | 1                     |
            | comments          | A longer comment      |
            | name              | My name               |
            | page              | Title of page         |
            | phone             | 054863476384          |
            | email             | myemail@emailhost.com |
