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
 * This page lists all the instances of qcreate in a particular course
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/qcreate/locallib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);
$PAGE->set_url('/mod/qcreate/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

$coursecontext = context_course::instance($id);
$params = array(
'context' => $coursecontext
);
$event = \mod_qcreate\event\course_module_instance_list_viewed::create($params);
$event->trigger();

$context = context_course::instance($course->id);
$qcreateobj = new qcreate($context, null, $course);

// Print the header.
$PAGE->navbar->add($qcreateobj->get_module_name_plural());
$PAGE->set_title($qcreateobj->get_module_name_plural());
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($qcreateobj->get_module_name_plural()));

require_capability('mod/qcreate:view', $context);

// Get the qcreate to render the page.
echo $qcreateobj->view('viewcourseindex');
