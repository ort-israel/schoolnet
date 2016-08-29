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
 * This class manage export of good questions form.
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once($CFG->dirroot . '/question/export_form.php');

class qcreate_export_good_questions_form extends question_export_form {
    protected function definition() {
        $mform    =& $this->_form;
        $qcreate   = $this->_customdata['qcreate'];
        if ($qcreate->graderatio != 100) {
            $mform->addElement('header', 'exportselection', get_string('exportselection', 'qcreate'));

            $menu = make_grades_menu($qcreate->grade);
            unset($menu[0]);
            $menu += array(0 => get_string('allquestions', 'qcreate'));
            $mform->addElement('select', 'betterthangrade', get_string('betterthangrade', 'qcreate'), $menu);
            $mform->setDefault('betterthangrade', 0);
        }
        $mform->addElement('header', 'exportnaming', get_string('exportnaming', 'qcreate'));

        $cbarray3 = array();
        $cbarray3[] = $mform->createElement('checkbox', 'naming[other]', '', get_string('specifictext', 'qcreate'));
        $cbarray3[] = $mform->createElement('text', 'naming[othertext]');
        $mform->setType('naming[othertext]', PARAM_TEXT);
        $mform->addGroup($cbarray3, 'naming3', '', array(' '), false);
        $mform->disabledIf('naming3', 'naming[other]');

        $cbarray1 = array();
        $cbarray1[] = $mform->createElement('checkbox', 'naming[firstname]', '', get_string('firstname'));
        $cbarray1[] = $mform->createElement('checkbox', 'naming[lastname]', '', get_string('lastname'));
        $cbarray1[] = $mform->createElement('checkbox', 'naming[username]', '', get_string('username', 'qcreate'));
        $mform->addGroup($cbarray1, 'naming1', '', array(' '), false);

        $cbarray2 = array();
        $cbarray2[] = $mform->createElement('checkbox', 'naming[activityname]', '', get_string('activityname', 'qcreate'));
        $cbarray2[] = $mform->createElement('checkbox', 'naming[timecreated]', '', get_string('timecreated', 'qcreate'));
        $mform->addGroup($cbarray2, 'naming2', '', array(' '), false);

        parent::definition();
    }
}
