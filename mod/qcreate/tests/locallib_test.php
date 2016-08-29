<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for (some of) mod/qcreate/locallib.php.
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/qcreate/locallib.php');
require_once($CFG->dirroot . '/mod/qcreate/tests/base_test.php');

/**
 * Unit tests for (some of) mod/qcreate/locallib.php.
 *
 * @copyright  Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_locallib_testcase extends mod_qcreate_base_testcase {

    public function test_count_user_questions() {
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance();

        $instance = $qcreate->get_instance();

        // Should start empty.
        $this->assertEquals(0, $qcreate->count_user_questions($this->students[0]->id));

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        // Now test again.
        $this->assertEquals(1, $qcreate->count_user_questions($this->students[0]->id));
        $this->assertEquals(0, $qcreate->count_user_questions($this->students[1]->id));
    }

    public function test_delete_instance() {
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance();

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        // TODO : simulate adding alocal grade and a gradebook grade.

        // Now try and delete.
        $this->assertEquals(true, $qcreate->delete_instance());
    }

    public function test_reset_userdata() {
        global $DB;

        $now = time();
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance(array('timeopen' => $now));

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        $this->assertEquals(1, $qcreate->count_user_questions());
        // Now try and reset.
        $data = new stdClass();
        $data->reset_qcreate = 1;
        $data->reset_gradebook_grades = 1;
        $data->courseid = $this->course->id;
        $data->timeshift = 24 * 60 * 60;
        $this->setUser($this->editingteachers[0]);
        $qcreate->reset_userdata($data);

        $this->assertEquals(0, $qcreate->count_user_questions());

        // Reload the instance data.
        $instance = $DB->get_record('qcreate', array('id' => $qcreate->get_instance()->id));
        $this->assertEquals($now + 24 * 60 * 60, $instance->timeopen);

        // Test reset using qcreate_reset_userdata().
        $qcreatetimeopen = $instance->timeopen; // Keep old updated value for comparison.
        $data->timeshift = 2 * 24 * 60 * 60;
        qcreate_reset_userdata($data);
        $instance = $DB->get_record('qcreate', array('id' => $qcreate->get_instance()->id));
        $this->assertEquals($qcreatetimeopen + 2 * 24 * 60 * 60, $instance->timeopen);

        // Create one more qcreate and reset, make sure time shifted for previous qcreate is not changed.
        $qcreate2 = $this->create_instance(array('timeopen' => $now));
        $qcreatetimeopen = $instance->timeopen;
        $data->timeshift = 3 * 24 * 60 * 60;
        $qcreate2->reset_userdata($data);
        $instance = $DB->get_record('qcreate', array('id' => $qcreate->get_instance()->id));
        $this->assertEquals($qcreatetimeopen, $instance->timeopen);
        $instance2 = $DB->get_record('qcreate', array('id' => $qcreate2->get_instance()->id));
        $this->assertEquals($now + 3 * 24 * 60 * 60, $instance2->timeopen);

        // Reset both qcreates using qcreate_reset_userdata() and make sure both qcreates have same date.
        $qcreatetimeopen = $instance->timeopen;
        $qcreate2timeopen = $instance2->timeopen;
        $data->timeshift = 4 * 24 * 60 * 60;
        qcreate_reset_userdata($data);
        $instance = $DB->get_record('qcreate', array('id' => $qcreate->get_instance()->id));
        $this->assertEquals($qcreatetimeopen + 4 * 24 * 60 * 60, $instance->timeopen);
        $instance2 = $DB->get_record('qcreate', array('id' => $qcreate2->get_instance()->id));
        $this->assertEquals($qcreate2timeopen + 4 * 24 * 60 * 60, $instance2->timeopen);
    }

    public function test_update_calendar() {
        global $DB;

        $this->setUser($this->editingteachers[0]);
        $userctx = context_user::instance($this->editingteachers[0]->id)->id;

        // Create a new qcreate.
        $now = time();
        $qcreate = $this->create_instance(array(
            'timeopen' => $now,
            'intro' => 'Some text',
            'introformat' => FORMAT_HTML
        ));

        // See if there is an event in the calendar.
        $params = array('modulename' => 'qcreate', 'instance' => $qcreate->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertNotEmpty($event);
        $this->assertContains('Some text', $event->description);

        // Make sure the same works when updating the qcreate.

        $formdata = $qcreate->get_instance();
        $formdata->timeopen = $now + 60;
        $formdata->allowed = array('multichoice' => 1, 'numerical' => 1);
        $formdata->qtype = array();
        $formdata->instance = $formdata->id;
        $formdata->intro = 'New text';
        $qcreate->update_instance($formdata);

        $params = array('modulename' => 'qcreate', 'instance' => $qcreate->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertNotEmpty($event);
        $this->assertContains('New text', $event->description);
    }

    public function test_update_instance() {
        global $DB;

        $now = time();
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance(array('studentqaccess' => 1, 'timeopen' => $now));

        $formdata = $qcreate->get_instance();
        $formdata->timeopen = $now + 60;
        $formdata->allowed = array('multichoice' => 1, 'numerical' => 1);
        $formdata->qtype = array();
        $formdata->instance = $formdata->id;
        $formdata->studentqaccess = 2;

        $qcreate->update_instance($formdata);

        $instance = $DB->get_record('qcreate', array('id' => $qcreate->get_instance()->id));
        $this->assertEquals($now + 60, $instance->timeopen);
        $this->assertEquals(2, $instance->studentqaccess);
        $this->assertEquals('multichoice,numerical', $instance->allowed);
    }

    public function test_qcreate_refresh_events() {
        global $DB;

        $now = time();
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance(array('timeopen' => $now));

        // See if there is an event in the calendar.
        $params = array('modulename' => 'qcreate', 'instance' => $qcreate->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertEquals($now, $event->timestart);

        // Change timeopen.
        $formdata = $qcreate->get_instance();
        $formdata->timeopen = $now + 60;
        $formdata->allowed = array('ALL' => 1);
        $formdata->qtype = array();
        $formdata->instance = $formdata->id;

        $qcreate->update_instance($formdata);
        qcreate_refresh_events();

        // See if the event has been updated.
        $params = array('modulename' => 'qcreate', 'instance' => $qcreate->get_instance()->id);
        $event = $DB->get_record('event', $params);
        $this->assertEquals($now + 60, $event->timestart);
    }

    public function test_get_graders() {
        $this->create_extra_users();
        $this->setUser($this->editingteachers[0]);

        // Create a qcreate with no groups.
        $qcreate = $this->create_instance();
        $this->assertCount(self::DEFAULT_TEACHER_COUNT +
                           self::DEFAULT_EDITING_TEACHER_COUNT +
                           self::EXTRA_TEACHER_COUNT +
                           self::EXTRA_EDITING_TEACHER_COUNT,
                           $qcreate->testable_get_graders($this->students[0]->id));

        // Force create a qcreate with SEPARATEGROUPS.
        $data = new stdClass();
        $data->courseid = $this->course->id;
        $data->name = 'Grouping';
        $groupingid = groups_create_grouping($data);
        groups_assign_grouping($groupingid, $this->groups[0]->id);
        $qcreate = $this->create_instance(array('groupingid' => $groupingid, 'groupmode' => SEPARATEGROUPS));

        $this->setUser($this->students[1]);
        $this->assertCount(4, $qcreate->testable_get_graders($this->students[0]->id));
        // Note the second student is in a group that is not in the grouping.
        // This means that we get all graders that are not in a group in the grouping.
        $this->assertCount(10, $qcreate->testable_get_graders($this->students[1]->id));
    }
}

