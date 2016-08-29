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
 * Quiz events tests.
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for qcreate events.
 *
 * @package    mod_qcreate
 * @category   phpunit
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_events_testcase extends advanced_testcase {

    /**
     * Test the edit page viewed event.
     *
     * There is no external API for updating a qcreate, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_edit_page_viewed() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qcreate = $this->getDataGenerator()->create_module('qcreate', array('course' => $course->id));

        $params = array(
            'courseid' => $course->id,
            'context' => context_module::instance($qcreate->cmid),
            'other' => array(
                'qcreateid' => $qcreate->id
            )
        );
        $event = \mod_qcreate\event\edit_page_viewed::create($params);
        $legacy = array($course->id, 'qcreate', 'editquestions', 'view.php?id=' . $qcreate->cmid, $qcreate->id, $qcreate->cmid);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_qcreate\event\edit_page_viewed', $event);
        $this->assertEquals(context_module::instance($qcreate->cmid), $event->get_context());
        $this->assertEventLegacyLogData($legacy, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the overview viewed event.
     *
     * There is no external API for viewing overview of a qcreate, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_overview_viewed() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $qcreate = $this->getDataGenerator()->create_module('qcreate', array('course' => $course->id));

        $params = array(
            'courseid' => $course->id,
            'context' => context_module::instance($qcreate->cmid),
            'other' => array(
                'qcreateid' => $qcreate->id
            )
        );
        $event = \mod_qcreate\event\overview_viewed::create($params);
        $legacy = array($course->id, 'qcreate', 'overview', 'overview.php?cmid=' . $qcreate->cmid, $qcreate->id, $qcreate->cmid);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_qcreate\event\overview_viewed', $event);
        $this->assertEquals(context_module::instance($qcreate->cmid), $event->get_context());
        $this->assertEventLegacyLogData($legacy, $event);
        $this->assertEventContextNotUsed($event);
    }
}
