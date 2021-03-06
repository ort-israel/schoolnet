@block @block_gismo
Feature: Visualize the use of assignment activity
	In order to visualize the use of assignment activity
	As a teacher
	I need to have the graphical representation of use of one assignment activity

	Background:
		Given the following "courses" exist:
			| fullname | shortname | category |
			| Course 1 | C1 | 0 |
		And the following "users" exist:
			| username | firstname | lastname | email |
			| student1 | Student | 1 | student1@asd.com |
			| teacher1 | Teacher | 1 | teacher1@asd.com |
		And the following "course enrolments" exist:
			| user | course | role |
			| student1 | C1 | student |
			| teacher1 | C1 | editingteacher |
	
	@javascript
	Scenario: Add one assignment and access Gismo overviews
		Given I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I turn editing mode on
		And I add the "Gismo" block
		And I add a "Assignment" to section "1" and I fill the form with:
			| Assignment name     | Test assignment name |
			| Description | Submit your online text |
		And I log out
		When I log in as "student1"
		And I am on homepage
		And I follow "Course 1"
		And I follow "Test assignment name"
		And I press "Add submission"
		And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
		And I wait until the page is ready
		And I press "Save changes"
		And I log out
		Then I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I follow "Test assignment name"
		# And I follow "View/grade all submissions" adapted for 3.1
		And I follow "all submissions"
		And I click on "Quick grading" "checkbox"
		And I set the field "User grade" to "100.00"
		And I press "Save all quick grading changes"
		And I should see "The grade changes were saved"
		And I press "Continue"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I synchronize gismo data
		And I go to the "Activities > Assignments" report
		And I should see "Grade: 100.00 / 100.00" on "Activities > Assignment grades" report
		And I wait "10" seconds
