@block @block_gismo
Feature: Visualize the use of url type resources with completion
	In order to visualize the use of url type resources with completion
	As a teacher
	I need to have the graphical representation of use of two url type resources
	
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
		And I log in as "admin"
		# "Access" is the common word of a property that has changed its name:
		# the "Enable restricted access" (version 3.0) to "Enable conditional access" (version <3.0). 
		And I set the following administration settings values:
			| Enable completion tracking | 1 |
			| access | 1 |
		And I log out

	@javascript
	Scenario: Add two url type resources with completion and access GISMO overviews
    	When I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I turn editing mode on
		And I follow "Edit settings"
		And I set the following fields to these values:
			| Enable completion tracking | Yes |
		And I press "Save"
		And I add the "Gismo" block
		And I add a "URL" to section "1" and I fill the form with:
			| Name | univ-lemans |
			| Description | Test URL description |
			| External URL | http://www.univ-lemans.fr |
		And I follow "univ-lemans"
		And I navigate to "Edit settings" node in "URL module administration"
		And I set the following fields to these values:
			| Completion tracking | Show activity as complete when conditions are met |
			| Student must view this activity to complete it | 1 |
		And I press "Save and return to course"
		And I add a "URL" to section "1" and I fill the form with:
			| Name | openStreetMap |
			| Description | Test URL description |
			| External URL | http://www.openstreetmap.org |
		And I follow "openStreetMap"
		And I navigate to "Edit settings" node in "URL module administration"
		And I set the following fields to these values:
			| Completion tracking | Show activity as complete when conditions are met |
			| Student must view this activity to complete it | 1 |
		And I press "Save and return to course"
		And I log out
		And I log in as "student1"
		And I am on homepage
		And I follow "Course 1"
		And I follow "univ-lemans"
		And I follow "openStreetMap"
		And I log out
		Then I log in as "teacher1"
		And I am on site homepage (New step defintion in version 2.9)
		And I follow "Course 1"
		And I synchronize gismo data
		And I should see "Completed" on "Completion > Resources" report
		And I wait "10" seconds