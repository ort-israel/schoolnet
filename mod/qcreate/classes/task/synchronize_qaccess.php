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
 * A scheduled task for qcreate activities to synchronize student's question access capabilities.
 *
 * @package    mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_qcreate\task;

/**
 * Synchronize students capabilites on questions for each instance of qcreate.
 */
class synchronize_qaccess extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('synchronizeqaccesstask', 'mod_qcreate');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/qcreate/lib.php');
        // Find all qcreate instances.
        $sql = "SELECT q.*, cm.id as cmidnumber, q.course as courseid
                FROM {qcreate} q, {course_modules} cm, {modules} m
                WHERE m.name='qcreate' AND m.id=cm.module AND cm.instance=q.id";
        $qcreates = $DB->get_recordset_sql($sql);
        if ($qcreates) {
            foreach ($qcreates as $qcreate) {
                $context = \context_module::instance($qcreate->cmidnumber);
                qcreate_student_q_access_sync($context, $qcreate, false);
            }
        }
    }

}
