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
 * Structure step to restore one qcreate activity
 *
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qcreate_activity_structure_step extends restore_questions_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $qcreate = new restore_path_element('qcreate', '/activity/qcreate');
        $paths[] = $qcreate;

        $paths[] = new restore_path_element('qcreate_required',
                '/activity/qcreate/requireds/required');

        if ($userinfo) {
            $paths[] = new restore_path_element('qcreate_grade', '/activity/qcreate/grades/grade');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_qcreate($data) {
        global $CFG, $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->grade < 0) { // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Supply some items that maybe missing from previous versions.
        $qcreateconfig = get_config('qcreate');
        if (!isset($data->completionquestions)) {
            $data->completionquestions = 0;
        }
        if (!isset($data->sendgradernotifications)) {
            $data->sendgradernotifications = $qcreateconfig->sendgradernotifications;
        }
        if (!isset($data->sendstudentnotifications)) {
            $data->sendstudentnotifications = $qcreateconfig->sendstudentnotifications;
        }

        // Insert the qcreate record.
        $newitemid = $DB->insert_record('qcreate', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_qcreate_required($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->qcreateid = $this->get_new_parentid('qcreate');

        $DB->insert_record('qcreate_required', $data);
    }

    protected function process_qcreate_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $questioncreated = $this->get_mappingid('question_created', $data->questionid);
        $data->qcreateid = $this->get_new_parentid('qcreate');
        $data->teacher = $this->get_mappingid('user', $data->teacher);
        $data->grade = $data->gradeval;
        $data->timemarked = $this->apply_date_offset($data->timemarked);
        if ($questioncreated) {
            // Adjust questionid.
            $data->questionid = $questioncreated;
        }
        $DB->insert_record('qcreate_grades', $data);
    }

    protected function inform_new_usage_id($newusageid) {

    }
    protected function after_execute() {
        parent::after_execute();
        // Add qcreate related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_qcreate', 'intro', null);
    }
}
