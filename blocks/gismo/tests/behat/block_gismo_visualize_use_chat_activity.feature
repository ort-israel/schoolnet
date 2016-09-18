@block @block_gismo
Feature: Visualize the use of chat activity
	In order to visualize the use of chat activity
	As a teacher
	I need to have the graphical representation of use of one chat activity

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
			
	@javascript @_switch_window
	Scenario: Add one chat and access GISMO overviews			
		When I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I turn editing mode on
		And I add the "Gismo" block
		And I add a "Chat" to section "1" and I fill the form with:
			| Name of this chat room | Chat room |
			| Description | Chat description |
		And I wait until the page is ready
		And I log out
		And I log in as "student1"
		And I am on homepage
		And I follow "Course 1"
		And I follow "Chat room"
		And I follow chat link "Use more accessible interface"
		And I set the field "Send message" to "test message"
		And I press "Submit"
		And I move backward one page
		And I move backward one page
		And I log out
		Then I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I synchronize gismo data
		And I go to the "Activities > Chats" report
		And I should see "1" on "Activities > Chats over time" report
		And I wait "10" seconds