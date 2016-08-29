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

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz module test data generator class
 *
 * @package mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_generator extends testing_module_generator {
    /**
     * Creates new qcreate module instance.
     *
     * @param array|stdClass $record data for module being generated. Requires 'course' key
     *     (an id or the full object). Also can have any fields from add module form.
     * @param null|array $options general options for course module. Since 2.6 it is
     *     possible to omit this argument by merging options into $record
     * @return stdClass record from module-defined table with additional field
     *     cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/qcreate/locallib.php');
        // Ensure the record can be modified without affecting calling code.
        $record = (object)(array)$record;

        $defaultqcreatesettings = array(
            'grade'                    => 100,
            'graderatio'               => 50,
            'allowed'                  => array('ALL' => 1),
            'qtype'                    => array(),
            'totalrequired'            => 1,
            'studentqaccess'           => 3,
            'timeopen'                 => 0,
            'timeclose'                => 0,
            'timesync'                 => 0,
            'timecreated'              => time(),
            'timemodified'             => time(),
            'completionquestions'      => 1,
            'sendgradernotifications'  => 0,
            'sendstudentnotifications' => 0
        );

        foreach ($defaultqcreatesettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
