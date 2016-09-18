@block @block_gismo
Feature: Visualize the use of wiki activity
	In order to visualize the use of wiki activity
	As a teacher
	I need to have the graphical representation of use of one wiki activity
	
	Background:
		Given the following "courses" exist:
			| fullname | shortname | category |
			| Course 1 | C1 | 0 |
		And the following "users" exist:
			| username | firstname | lastname | email |
			| student1 | Student | 1 | student1@asd.com |
            | teacher1 | Teacher | 1 | teacher1@example.com |
		And the following "course enrolments" exist:
			| user | course | role |
			| student1 | C1 | student |
			| teacher1 | C1 | editingteacher |

	@javascript
	Scenario: Add one wiki and access GISMO overviews
		When I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I turn editing mode on
		And I add the "Gismo" block
		And I add a "Wiki" to section "1" and I fill the form with:
			| Wiki name | Collaborative wiki name |
			| Description | Collaborative wiki description |
			| First page name | Collaborative index |
			| Wiki mode | Collaborative wiki |
		And I log out
		And I log in as "student1"
		And I am on homepage
		And I follow "Course 1"
		And I follow "Collaborative wiki name"
		And I press "Create page"
		And I set the following fields to these values:
			| HTML format | Collaborative teacher1 edition |
		And I press "Save"
		And I am on homepage
		And I log out
		Then I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I synchronize gismo data
		And I go to the "Activities > Wikis" report
		And I should see "2" on "Activities > Wikis over time" report
		And I wait "10" seconds