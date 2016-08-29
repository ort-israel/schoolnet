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
 * PHPUnit data generator tests
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2012 Matt Petro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit data generator testcase
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_generator_testcase extends advanced_testcase {
    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('qcreate', array('course' => $course->id)));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qcreate');
        $this->assertInstanceOf('mod_qcreate_generator', $generator);
        $this->assertEquals('qcreate', $generator->get_modulename());

        $qcreate = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('qcreate', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('qcreate', array('course' => $course->id, 'id' => $qcreate->id)));
        $this->assertEquals(1, $DB->count_records('qcreate'));

        $cm = get_coursemodule_from_instance('qcreate', $qcreate->id);
        $this->assertEquals($qcreate->id, $cm->instance);
        $this->assertEquals('qcreate', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($qcreate->cmid, $context->instanceid);
        $this->assertTrue($DB->record_exists('question_categories', array('contextid' => $context->id)));

        $params = array('course' => $course->id, 'name' => 'One more qcreate');
        $qcreate = $this->getDataGenerator()->create_module('qcreate', $params);
        $this->assertEquals(2, $DB->count_records('qcreate', array('course' => $course->id)));
        $this->assertEquals('One more qcreate', $DB->get_field_select('qcreate', 'name', 'id = :id', array('id' => $qcreate->id)));
    }
}
