@block @block_coursesearch
Feature: Block course search
  In order to search for a course
  As a Moodle Administrator or any other user
  I can add coursesearch block on the site homepage or on the dashboard

  Background:
    Given the following "categories" exist:
      | name | category | idnumber |
      | Science | 0 | SCI |
      | English | 0 | ENG |
      | Miscellaneous | 0 | MISC |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Biology Y1 | BIO1 | MISC |
      | Biology Y2 | BIO2 | MISC |
      | English Y1 | ENG1 | ENG |
      | English Y2 | ENG2 | MISC |
    And the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | student1 | Student | 1 | student1@example.com | S1 |

  Scenario: Add coursesearch block on the dashboard
    When I log in as "student1"
    And I am on dashboard
    And I turn editing mode on
    And I add the "Basic course search" block
    When I set the field "Search courses" to "Biology"
    And I click on "Search" "button" in the "Basic course search" "block"
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should not see "English Y1"
    And I should not see "English Y2"

  @javascript
  Scenario: Add coursesearch block on the site homepage
    When I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    And I add the "Basic course search" block
    And I am on site homepage
    When I set the field "Search courses" to "Biology"
    And I click on "Search" "button" in the "Basic course search" "block"
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should not see "English Y1"
    And I should not see "English Y2"
