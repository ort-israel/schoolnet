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
 * Instance add/edit form
 *
 * @package    mod_qcreate
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/mod/qcreate/locallib.php');
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/questionlib.php');

class mod_qcreate_mod_form extends moodleform_mod {

    private $_requireds;

    protected function definition() {

        global $COURSE, $DB;
        $mform    =& $this->_form;

        $qcreateconfig = get_config('qcreate');
        $qcreate = new stdClass();
        $qcreate->id = $this->_instance;
        if (!empty($this->_instance)) {
            $this->_requireds = qcreate_required_qtypes($qcreate);
        } else {
            $this->_requireds = array();
        }

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('intro', 'qcreate'));

        // Open and close time.
        $mform->addElement('header', 'timinghdr', get_string('availability', 'qcreate'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('open', 'qcreate'), array('optional' => true));
        $mform->addHelpButton('timeopen', 'open', 'qcreate');
        $mform->addElement('date_time_selector', 'timeclose', get_string('close', 'qcreate'), array('optional' => true));

        // Grading.
        $mform->addElement('header', 'gradeshdr', get_string('grading', 'qcreate'));
        $gradeoptions = array();
        $gradeoptions[0] = get_string('nograde');
        for ($i = 100; $i >= 1; $i--) {
            $gradeoptions[$i] = $i;
        }
        $mform->addElement('select', 'grade', get_string('grade'), $gradeoptions);
        $mform->setDefault('grade', 100);
        $mform->addHelpButton('grade', 'grade', 'qcreate');

        $graderatiooptions = array();
        foreach (array(100, 90, 80, 67, 60, 50, 40, 33, 30, 20, 10, 0) as $graderatiooption) {
            $a = new stdClass();
            $a->automatic = ($graderatiooption).'%';
            $a->manual = (100 - ($graderatiooption)).'%';
            $graderatiooptions[$graderatiooption] = get_string('graderatiooptions', 'qcreate', $a);
        }
        $mform->addElement('select', 'graderatio', get_string('graderatio', 'qcreate'), $graderatiooptions);
        $mform->setDefault('graderatio', 50);
        $mform->addHelpButton('graderatio', 'graderatio', 'qcreate');
        $mform->setExpanded('gradeshdr', true);

        $allowedgroup = array();
        $allowedgroup[] =& $mform->createElement('checkbox', "ALL", '', get_string('allowall', 'qcreate'));

        $qtypemenu = qcreate::qtype_menu();
        $allowedqtypes = array();
        foreach ($qtypemenu as $qtype => $name) {
            $allowedgroup[] =& $mform->createElement('checkbox', $qtype, '', $name);
            $allowedqtypes[$qtype] = $name;
        }
        if (question_bank::is_qtype_installed('multichoice')) {
            $mform->setDefault("allowed[multichoice]", 1);
        } else {
            $mform->setDefault("allowed[ALL]", 1);
        }
        $mform->addGroup($allowedgroup, 'allowed', get_string('allowedqtypes', 'qcreate'));
        $mform->disabledIf('allowed', "allowed[ALL]", 'checked');
        $mform->addHelpButton('allowed', 'allowedqtypes', 'qcreate');

        for ($i = 1; $i <= 20; $i++) {
            $noofquestionsmenu[$i] = $i;
        }
        $mform->addElement('select', 'totalrequired', get_string('noofquestionstotal', 'qcreate'), $noofquestionsmenu);
        $mform->addHelpButton('totalrequired', 'noofquestionstotal', 'qcreate');

        $mform->addElement('header', 'addminimumquestionshdr', get_string('addminimumquestionshdr', 'qcreate'));
        $repeatarray = array();
        $qtypeselect = array('' => get_string('selectone', 'qcreate')) + $allowedqtypes;
        $repeatarray[] =& $mform->createElement('select', 'qtype', get_string('qtype', 'qcreate'), $qtypeselect);
        $repeatarray[] =& $mform->createElement('select', 'minimumquestions', get_string('minimumquestions', 'qcreate'),
                $noofquestionsmenu);
        $requiredscount = count($this->_requireds);
        $repeats = $this->_requireds ? $requiredscount + 2 : 4;
        $repeats = $this->repeat_elements($repeatarray, $repeats, array(), 'minrepeats', 'addminrepeats', 2,
                get_string('addmorerequireds', 'qcreate'), true);

        for ($i = 0; $i < $repeats; $i++) {
            $mform->addHelpButton("qtype[$i]", 'qtype', 'qcreate');
            $mform->addHelpButton("minimumquestions[$i]", 'minimumquestions', 'qcreate');
            $mform->disabledIf("minimumquestions[$i]", "qtype[$i]", 'eq', '');
        }

        // Sudents access rights on their own questions.
        $mform->addElement('header', 'studentaccessheader', get_string('studentaccessheader', 'qcreate'));
        $studentqaccessmenu = array(0 => get_string('studentaccessaddonly', 'qcreate'),
                                1 => get_string('studentaccesspreview', 'qcreate'),
                                2 => get_string('studentaccesssaveasnew', 'qcreate'),
                                3 => get_string('studentaccessedit', 'qcreate'));
        $mform->addElement('select', 'studentqaccess', get_string('studentqaccess', 'qcreate'), $studentqaccessmenu);
        $mform->addHelpButton('studentqaccess', 'studentqaccess', 'qcreate');

        // Notifications.
        $mform->addElement('header', 'notifications', get_string('notifications', 'qcreate'));

        $name = get_string('sendgradernotifications', 'qcreate');
        $mform->addElement('selectyesno', 'sendgradernotifications', $name);
        $mform->addHelpButton('sendgradernotifications', 'sendgradernotifications', 'qcreate');
        $mform->setDefault('sendgradernotifications', $qcreateconfig->sendgradernotifications);

        $name = get_string('sendstudentnotifications', 'qcreate');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotifications', 'qcreate');
        $mform->setDefault('sendstudentnotifications', $qcreateconfig->sendstudentnotifications);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    public function data_preprocessing(&$defaultvalues) {
        $i = 0;
        if ($this->_requireds) {
            foreach ($this->_requireds as $qtype => $required) {
                $defaultvalues["minimumquestions[$i]"] = $required->no;
                $defaultvalues["qtype[$i]"] = $qtype;
                $i++;
            }
        }
        if (isset($defaultvalues['allowed'])) {
            $enabled = explode(',', $defaultvalues['allowed']);
            $qtypemenu = qcreate::qtype_menu();
            foreach ($qtypemenu as $qtype => $notused) {
                $defaultvalues["allowed[$qtype]"] = (array_search($qtype, $enabled) !== false) ? 1 : 0;
            }
            $defaultvalues["allowed[ALL]"] = (array_search('ALL', $enabled) !== false) ? 1 : 0;
        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $defaultvalues['completionquestionsenabled'] =
            !empty($defaultvalues['completionquestions']) ? 1 : 0;
        if (empty($defaultvalues['completionquestions'])) {
            $defaultvalues['completionquestions'] = 1;
        }

    }

    public function validation($data, $files) {
        $errors = array();
        if (!isset($data['allowed'])) {
            $errors['allowed'] = get_string('needtoallowatleastoneqtype', 'qcreate');
        }
        $totalrequired = 0;
        if (isset($data['qtype'])) {
            foreach ($data['qtype'] as $key => $qtype) {
                if ($qtype != '') {
                    $chkqtypes[$key] = $qtype;
                    $keysforthisqtype = array_keys($chkqtypes);
                    if (count(array_keys($data['qtype'], $qtype)) > 1) {
                        $errors["qtype[$key]"] = get_string('morethanonemin', 'qcreate', question_bank::get_qtype_name($qtype));

                    } else if (!isset($data['allowed'][$qtype]) && !isset($data['allowed']['ALL'])) {
                        $errors['allowed'] = get_string('needtoallowqtype', 'qcreate', question_bank::get_qtype_name($qtype));
                        $errors["qtype[$key]"] = get_string('needtoallowqtype', 'qcreate', question_bank::get_qtype_name($qtype));
                    }
                    $totalrequired += $data['minimumquestions'][$key];
                }

            }
        }
        if ($totalrequired > $data['totalrequired']) {
            $errors['totalrequired'] = get_string('totalrequiredislessthansumoftotalsforeachqtype', 'qcreate');
        }
        if (isset($data['allowed']['ALL']) && (count($data['allowed']) > 1)) {
            $errors['allowed'] = get_string('allandother', 'qcreate');
        }
        if (($data['timeclose'] != 0) && ($data['timeopen'] != 0) && ($data['timeclose'] <= $data['timeopen'])) {
            $errors['timeopen'] = get_string('openmustbemorethanclose', 'qcreate');
        }

        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completionquestionsenabled',
                '', get_string('completionquestions', 'qcreate'));
        $group[] =& $mform->createElement('text', 'completionquestions', '', array('size' => 3));
        $group[] =& $mform->createElement('static', 'staticthing', '', get_string('questions', 'qcreate') );
        $mform->setType('completionquestions', PARAM_INT);
        $mform->addGroup($group, 'completionquestionsgroup', get_string('completionquestionsgroup', 'qcreate'), array(' '), false);
        $mform->addHelpButton('completionquestionsgroup', 'completionquestions', 'qcreate');
        $mform->disabledIf('completionquestions', 'completionquestionsenabled', 'notchecked');

        return array('completionquestionsgroup');
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionquestionsenabled']) && $data['completionquestions'] != 0);

    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionquestionsenabled) || !$autocompletion) {
                $data->completionquestions = 0;
            }
        }
        return $data;
    }
}
