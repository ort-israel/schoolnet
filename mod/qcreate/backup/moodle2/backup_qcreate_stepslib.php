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
 * @package     mod_qcreate
 * @copyright   2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_qcreate_activity_task
 *
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qcreate_activity_structure_step extends backup_questions_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $qcreate = new backup_nested_element('qcreate', array('id'), array(
            'name', 'intro', 'introformat', 'grade', 'graderatio', 'allowed', 'totalrequired',
            'studentqaccess', 'timesync', 'timeopen', 'timeclose', 'timecreated',
            'timemodified', 'completionquestions', 'sendgradernotifications', 'sendstudentnotifications'));

        $requireds = new backup_nested_element('requireds');

        $required = new backup_nested_element('required', array('id'), array(
            'qtype', 'no'));

        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'), array(
            'questionid', 'gradeval', 'gradecomment', 'teacher', 'timemarked'));

        // Build the tree.
        $qcreate->add_child($requireds);
        $requireds->add_child($required);

        $qcreate->add_child($grades);
        $grades->add_child($grade);

        // Define sources.
        $qcreate->set_source_table('qcreate', array('id' => backup::VAR_ACTIVITYID));

        $required->set_source_table('qcreate_required',
                array('qcreateid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('qcreate_grades', array('qcreateid' => backup::VAR_PARENTID));
        }

        // Define source alias.
        $grade->set_source_alias('grade', 'gradeval');

        // Define id annotations.
        $grade->annotate_ids('user', 'teacher');

        // Define file annotations.
        $qcreate->annotate_files('mod_qcreate', 'intro', null); // This file area hasn't itemid.

        // Return the root element (qcreate), wrapped into standard activity structure.
        return $this->prepare_activity_structure($qcreate);
    }
}
