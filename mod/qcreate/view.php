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
 * This page prints a particular instance of qcreate
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/qcreate/lib.php');
require_once($CFG->dirroot . '/mod/qcreate/locallib.php');


$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // qcreate ID
$delete  = optional_param('delete', 0, PARAM_INT);  // Question id to delete.
$confirm  = optional_param('confirm', 0, PARAM_BOOL);
$qaction  = optional_param('qaction', '', PARAM_ALPHA); // Return from question bank.
$lastchanged = optional_param('lastchanged', 0, PARAM_INT); // Id of created or edited question.

$thisurl = new moodle_url('/mod/qcreate/view.php');
if ($id) {
    if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $qcreate = $DB->get_record("qcreate", array("id" => $cm->instance))) {
        print_error("Course module is incorrect");
    }
    $thisurl->param('id', $id);
} else {
    if (! $qcreate = $DB->get_record("qcreate", array("id" => $a))) {
        print_error('invalidqcreateid', 'qcreate');
    }
    if (! $course = $DB->get_record("course", array("id" => $qcreate->course))) {
        print_error('invalidcourseid');
    }
    if (! $cm = get_coursemodule_from_instance("qcreate", $qcreate->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $thisurl->param('a', $a);
}
$modulecontext = context_module::instance($cm->id);

if (!$cats = get_categories_for_contexts($modulecontext->id)) {
    debugging('default category not set', DEBUG_DEVELOPER);
}

if (has_capability('mod/qcreate:grade', $modulecontext)) {
    redirect($CFG->wwwroot.'/mod/qcreate/edit.php?cmid='.$cm->id);
}
$qcreate->cmidnumber = $cm->id;

$thisurl = new moodle_url('/mod/qcreate/view.php', array('id' => $cm->id));
$PAGE->set_url($thisurl);

$qcreateobj = new qcreate($modulecontext, $cm, $course);

qcreate_student_q_access_sync($modulecontext, $qcreateobj->get_instance());

require_login($course, true, $cm);

require_capability('mod/qcreate:view', $modulecontext);

// Update completion state.
$completion = new completion_info($course);
if ($completion->is_enabled($cm) && $qcreateobj->get_instance()->completionquestions) {
    $completion->update_state($cm, COMPLETION_COMPLETE);
}
$completion->set_module_viewed($cm);

if ($lastchanged &&($qaction == 'edit' || $qaction == 'add')) {
    qcreate_update_grades($qcreate, $USER->id);
    $params['cid'] = $qcreateobj->get_question_category()->id;
    $params['lastchanged'] = $lastchanged;
    if (!$question = $DB->get_record_select('question', "id = :lastchanged AND category = :cid", $params)) {
        print_error('question_not_found');
    } else {
        $qcreateobj->notify_graders($question);
    }
}

if ($delete && question_has_capability_on($delete, 'edit')) {
    if ($confirm && confirm_sesskey()) {
        $params['cid'] = $qcreateobj->get_question_category()->id;
        $params['deleteid'] = $delete;
        if (!$question = $DB->get_record_select('question', "id = :deleteid AND category = :cid", $params)) {
            print_error('question_not_found');
        } else {
            $DB->delete_records('qcreate_grades',
                    array('qcreateid' => $qcreateobj->get_instance()->id, 'questionid' => $question->id));
            question_delete_question($question->id);
            qcreate_update_grades($qcreate, $USER->id);
            // Update completion state.
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $qcreateobj->get_instance()->completionquestions) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE);
            }

            redirect($CFG->wwwroot.'/mod/qcreate/view.php?id='.$cm->id);
        }
    } else {
        echo $qcreateobj->view(optional_param('action', 'confirmdelete', PARAM_TEXT));
        die;
    }
}

// Get the qcreate class to render the view page.
echo $qcreateobj->view(optional_param('action', 'view', PARAM_TEXT));
