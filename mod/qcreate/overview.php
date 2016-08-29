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
 * This page prints an overview of a particular instance of qcreate for someone with grading permission
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/qcreate/lib.php');
require_once($CFG->dirroot.'/mod/qcreate/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');


list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) = question_edit_setup('questions', true);
$qcreate->cmidnumber = $cm->id;
require_capability('mod/qcreate:grade', context_module::instance($cm->id));

$modulecontext = context_module::instance($cm->id);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course->id);

require_capability('mod/qcreate:grade', $modulecontext);
$PAGE->set_url('/mod/qcreate/overview.php', array('cmid' => $cm->id));

$qcreateobj = new qcreate($modulecontext, $cm, $course);

// Get the qcreate class to render the overview page.
echo $qcreateobj->view(optional_param('action', 'overview', PARAM_TEXT));
