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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    mod_qcreate
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Qcreate conversion handler
 */
class moodle1_mod_qcreate_handler extends moodle1_mod_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    /** @var int cmid */
    protected $moduleid = null;

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'qcreate', '/MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE',
                array(
                    'newfields' => array(
                        'completionquestions'       => 0,
                    ),
                )
            ),
            new convert_path('qcreate_requireds',
                    '/MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE/REQUIREDS'),
            new convert_path('qcreate_required',
                    '/MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE/REQUIREDS/REQUIRED'),
            new convert_path('qcreate_grades',
                    '/MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE/GRADES'),
            new convert_path('qcreate_grade',
                    '/MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE/GRADE')
        );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/QCREATE
     * data available
     */
    public function process_qcreate($data) {
        global $CFG;

        // Get the course module id and context id.
        $instanceid     = $data['id'];
        $cminfo         = $this->get_cminfo($instanceid);
        $this->moduleid = $cminfo['id'];
        $contextid      = $this->converter->get_contextid(CONTEXT_MODULE, $this->moduleid);

        // Get a fresh new file manager for this instance.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_qcreate');

        // Convert course files embedded into the intro.
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $data['intro'] = moodle1_converter::migrate_referenced_files(
                $data['intro'], $this->fileman);

        // Start writing qcreate.xml.
        $this->open_xml_writer("activities/qcreate_{$this->moduleid}/qcreate.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid,
                'moduleid' => $this->moduleid, 'modulename' => 'qcreate',
                'contextid' => $contextid));
        $this->xmlwriter->begin_tag('qcreate', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }

        return $data;
    }

    public function on_qcreate_requireds_start() {
        $this->xmlwriter->begin_tag('requireds');
    }

    public function on_qcreate_requireds_end() {
        $this->xmlwriter->end_tag('requireds');
    }

    public function process_qcreate_required($data) {
        $this->write_xml('required', $data, array('/required/id'));
    }

    public function on_qcreate_grades_start() {
        $this->xmlwriter->begin_tag('grades');
    }

    public function on_qcreate_grades_end() {
        $this->xmlwriter->end_tag('grades');
    }

    public function process_qcreate_grade($data) {
        $this->write_xml('grade', $data, array('/grade/id'));
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'qcreate' path
     */
    public function on_qcreate_end() {
        // Finish writing qcreate.xml.
        $this->xmlwriter->end_tag('qcreate');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml.
        $this->open_xml_writer("activities/qcreate_{$this->moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
