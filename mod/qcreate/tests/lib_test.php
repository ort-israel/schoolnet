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
 * Unit tests for (some of) mod/qcreate/lib.php.
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/qcreate/lib.php');
require_once($CFG->dirroot . '/mod/qcreate/locallib.php');
require_once($CFG->dirroot . '/mod/qcreate/tests/base_test.php');

/**
 * Unit tests for (some of) mod/qcreate/lib.php.
 *
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_lib_testcase extends mod_qcreate_base_testcase {

    protected function setUp() {
        parent::setUp();

        // Add additional default data.

    }

    public function test_qcreate_print_overview() {
        global $DB;
        $courses = $DB->get_records('course', array('id' => $this->course->id));
        $qcreate = $this->create_instance();

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        // Check the overview as the different users.
        $overview = array();
        qcreate_print_overview($courses, $overview);
        $this->assertEquals(count($overview), 1);

        $this->setUser($this->teachers[0]);
        $overview = array();
        qcreate_print_overview($courses, $overview);
        $this->assertEquals(count($overview), 1);

        $this->setUser($this->editingteachers[0]);
        $overview = array();
        qcreate_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
    }

    public function test_print_recent_activity() {
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance();

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        $this->expectOutputRegex('/New questions created:/');
        qcreate_print_recent_activity($this->course, true, time() - 3600);
    }

    public function test_qcreate_get_recent_mod_activity() {
        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance();

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        $activities = array();
        $index = 0;

        $activity = new stdClass();
        $activity->type    = 'activity';

        $activity->cmid    = $qcreate->get_course_module()->id;
        $activities[$index++] = $activity;

        qcreate_get_recent_mod_activity( $activities,
                                        $index,
                                        time() - 3600,
                                        $this->course->id,
                                        $qcreate->get_course_module()->id);

        $this->assertEquals("qcreate", $activities[1]->type);
    }

    public function test_qcreate_user_complete() {
        global $PAGE;

        $this->setUser($this->editingteachers[0]);
        $qcreate = $this->create_instance();

        $PAGE->set_url(new moodle_url('/mod/qcreate/view.php', array('id' => $qcreate->get_course_module()->id)));

        $this->expectOutputRegex('/Grade: -/');
        qcreate_user_complete($this->course, $this->students[0], $qcreate->get_course_module(), $qcreate->get_instance());

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        $this->expectOutputRegex('/Grade: -/');

        qcreate_user_complete($this->course, $this->students[0], $qcreate->get_course_module(), $qcreate->get_instance());
    }

    public function test_qcreate_get_completion_state() {
        $qcreate = $this->create_instance();

        $this->setUser($this->students[0]);
        $result = qcreate_get_completion_state($this->course, $qcreate->get_course_module(), $this->students[0]->id, false);
        $this->assertFalse($result);

        // Create a question as student0.
        $this->setUser($this->students[0]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $standardq = $questiongenerator->create_question('shortanswer', null,
                array('category' => $qcreate->get_question_category()->id));

        $result = qcreate_get_completion_state($this->course, $qcreate->get_course_module(), $this->students[0]->id, false);

        $this->assertTrue($result);
    }
}
