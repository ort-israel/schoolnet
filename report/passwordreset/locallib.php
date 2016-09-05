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
 * Local library functions for the passwordreset report.
 *
 * @package   report_passwordreset
 * @copyright 2013 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('PASSWORDRESET_MODE_DISPLAY', 'display');
define('PASSWORDRESET_MODE_PRINT', 'print');

function report_passwordreset_get_group_options($id) {
    $groupsfromdb = groups_get_all_groups($id);
    $groups = array();
    foreach ($groupsfromdb as $key => $value) {
        $groups[$key] = $value->name;
    }
    return $groups;
}

function report_passwordreset_output_action_buttons($id, $group, $mode, $url, $filteruser) {
    global $OUTPUT;


    // add select group
    $groups = report_passwordreset_get_group_options($id);

    $groupurl = clone $url;
    $groupurl->params(array('mode' => $mode));
    $modeurl = clone $url;
    $modeurl->params(array('group' => $group));

    $select = new single_select($groupurl, 'group', $groups, $group, array('' => get_string('allusers', 'report_passwordreset')));
    $select->label = get_string('group');
    $html = html_writer::start_tag('div');
    $html .= $OUTPUT->render($select);
    $html .= html_writer::end_tag('div');

    // add user search
    $searchuserurl = clone $url;
    $searchuserurl->params(array('id' => $id));

    $html .= html_writer::start_tag('div');
    $html .= html_writer::start_tag('form', array('method' => 'get', 'action' => $searchuserurl, 'id' => 'searchuser'));
    $html .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
    $html .= html_writer::label(get_string('search'), 'filteruser');
    $html .= html_writer::tag('input', '', array('type' => 'text', 'name' => 'filteruser', 'value' => $filteruser));
    $html .= html_writer::tag('input', '', array('type' => 'submit', 'value' => 'Search'));
    $html .= html_writer::end_tag('form');
    $html .= html_writer::end_tag('div');
    return $html;
}
