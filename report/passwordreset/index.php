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
 * Reset a student's password, by displaying a rastor of students.
 *
 * @package   report_passwordreset
 * @copyright 2013 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT);
$mode = optional_param('mode', PASSWORDRESET_MODE_DISPLAY, PARAM_TEXT);
$group = optional_param('group', 0, PARAM_INT);
$filteruser = optional_param('filteruser', '', PARAM_RAW);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// Setup page.
$PAGE->set_url('/report/passwordreset/index.php', array('id' => $id));
if ($mode === PASSWORDRESET_MODE_PRINT) {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagelayout('report');
}
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/passwordreset:view', $coursecontext);

// Get all the users. Lea 2015/12 - username added for search
$userlist = get_enrolled_users($coursecontext, '', $group, user_picture::fields('u', array('username'), 0, 0, true));

// Get suspended users.
$suspended = get_suspended_userids($coursecontext);


$data = array();
foreach ($userlist as $user) {

    // search for user according to the filteruser, if exists. If search is not found in current user, skip user
    if (!empty($filteruser)
        && mb_strpos($user->firstname, $filteruser) === false
        && mb_strpos($user->lastname, $filteruser) === false
        && mb_strpos($user->username, $filteruser) === false
    ) {
        continue;
    }
    if (!in_array($user->id, $suspended)) {
        $resetpasswordurl = new moodle_url('resetpassword.php', array('userid' => $user->id, 'id' => $course->id, 'sesskey' => sesskey()));

        $item = $OUTPUT->user_picture($user, array('size' => 64, 'courseid' => $course->id));
        $item .= html_writer::tag('span', fullname($user));
        $item .= html_writer::link($resetpasswordurl, get_string('resetpassword', 'report_passwordreset'), array('class' => 'btn'));
        $data[] = $item;
    }
}

// Finish setting up page.
$PAGE->set_title($course->shortname . ': ' . get_string('passwordresettitle', 'report_passwordreset'));
$PAGE->set_heading($course->fullname);

// Display the passwordreset to the user.
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('passwordresettitle', 'report_passwordreset'));
echo report_passwordreset_output_action_buttons($id, $group, $mode, $PAGE->url, $filteruser);
echo html_writer::alist($data, array('class' => 'report-passwordreset'));
echo $OUTPUT->footer();
