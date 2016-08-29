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
 * This file contains a renderer for the qcreatement class
 *
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/qcreate/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the qcreate module.
 *
 * @package mod_qcreate
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_qcreate_renderer extends plugin_renderer_base {

    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a grading message notification
     * @param qcreate_gradingmessage $result The result to render
     * @return string
     */
    public function render_qcreate_gradingmessage(qcreate_gradingmessage $result) {
        $urlparams = array('id' => $result->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/qcreate/view.php', $urlparams);
        $classes = $result->gradingerror ? 'notifyproblem' : 'notifysuccess';

        $o = '';
        $o .= $this->output->heading($result->heading, 4);
        $o .= $this->output->notification($result->message, $classes);
        $o .= $this->output->continue_button($url);
        return $o;
    }

    /**
     * Render a course index summary
     *
     * @param qcreate_course_index_summary $indexsummary
     * @return string
     */
    public function render_qcreate_course_index_summary(qcreate_course_index_summary $indexsummary) {
        $o = '';

        $strplural = get_string('modulenameplural', 'qcreate');
        $strsectionname  = $indexsummary->courseformatname;
        $stropen = get_string('openon', 'qcreate');
        $strclose = get_string('closeon', 'qcreate');
        $strqcreated = get_string('createdquestions', 'qcreate');
        $strgrade = get_string('grade');

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head  = array ($strsectionname, $strplural, $stropen, $strclose, $strqcreated, $strgrade);
            $table->align = array ('left', 'left', 'left', 'left', 'center', 'right');
        } else {
            $table->head  = array ($strplural, $strqcreated, $stropen, $strclose, $strgrade);
            $table->align = array ('left', 'left', 'left', 'center', 'right');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->qcreates as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(new moodle_url('/mod/qcreate/view.php', $params),
                                      $info['cmname']);

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            $open = $info['timeopen'] ? userdate($info['timeopen']) : '-';
            $close = $info['timeclose'] ? userdate($info['timeclose']) : '-';

            if ($indexsummary->usesections) {
                $row = array($printsection, $link, $open, $close, $info['questions'], $info['gradeinfo']);
            } else {
                $row = array($link, $open, $close, $info['questions'], $info['gradeinfo']);
            }
            $table->data[] = $row;
        }

        $o .= html_writer::table($table);

        return $o;
    }

    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * Render the header.
     *
     * @param qcreate_header $header
     * @return string
     */
    public function render_qcreate_header(qcreate_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'qcreate'));
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->qcreate->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('qcreate', $header->qcreate, $header->coursemoduleid);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the time status.
     *
     * @param bool available is the qcreate available
     * @param int timeopen
     * @param int timeclose
     * @return string
     */
    public function time_status($available, $timenow, $timeopen, $timeclose) {
        if ($available) {
            $openstring = get_string('activityopen', 'qcreate');
        } else {
            $openstring = get_string('activityclosed', 'qcreate');
        }
        $string = html_writer::tag('em', $openstring);
        if (!$timeopen && !$timeclose) {
            return $string.' '.get_string('timenolimit', 'qcreate');
        }
        if ($timeopen) {
            if ($timenow < $timeopen) {
                $string .= ' '.get_string('timewillopen', 'qcreate', userdate($timeopen));
            } else {
                $string .= ' '.get_string('timeopened', 'qcreate', userdate($timeopen));
            }
        }
        if ($timeclose) {
            if ($timenow < $timeclose) {
                $string .= ' '.get_string('timewillclose', 'qcreate', userdate($timeclose));
            } else {
                $string .= ' '.get_string('timeclosed', 'qcreate', userdate($timeclose));
            }
        }
        return $string;
    }

    public function gradingstring($graderatio) {
        if ($graderatio == 100) {
            $gradestring = get_string('gradeallautomatic', 'qcreate');
        } else if ($graderatio == 0) {
            $gradestring = get_string('gradeallmanual', 'qcreate');
        } else {
            $gradeobj = new stdClass();
            $gradeobj->automatic = $graderatio;
            $gradeobj->manual = 100 - $graderatio;
            $gradestring = get_string('grademixed', 'qcreate', $gradeobj);
        }
        return $gradestring;
    }

    public function proper_grammar($arrayitems) {
        $grammarised = array();
        foreach ($arrayitems as $key => $arrayitem) {
            if ($arrayitem->no > 1) {
                $arrayitem->qtypestring = question_bank::get_qtype_name($arrayitem->qtype);
                $grammarised[$key] = get_string('requiredplural', 'qcreate', $arrayitem);
            } else {
                $arrayitem->qtypestring = question_bank::get_qtype_name($arrayitem->qtype);
                $grammarised[$key] = get_string('requiredsingular', 'qcreate', $arrayitem);
            }
        }
        return $grammarised;
    }

    public function proper_punctuation($arrayitems) {
        $i = 1;
        $listitems = array();
        foreach ($arrayitems as $key => $arrayitem) {
            // All but last and last but one items.
            if ($i < (count($arrayitems) - 1)) {
                $listitems[$key] = get_string('comma', 'qcreate', $arrayitem);
            }
            // Last but one item.
            if ($i == (count($arrayitems) - 1)) {
                $listitems[$key] = get_string('and', 'qcreate', $arrayitem);
            }
            if ($i == (count($arrayitems))) {
                // Last item.
                $listitems[$key] = get_string('fullstop', 'qcreate', $arrayitem);
            }
            $i++;
        }
        return $listitems;
    }

    private function teacher_required_questions($requireds, $totalrequired) {
        $qtyperequired = 0;
        if ($requireds) {
            $grammarised = $this->proper_grammar($requireds);
            foreach ($requireds as $qtype => $required) {
                $qtyperequired += $required->no;
            }
        }
        if ($qtyperequired < $totalrequired) {
            $a = new stdClass();
            $a->extrarequired = $totalrequired - $qtyperequired;
            if ($a->extrarequired == 1) {
                $grammarised['extras'] = get_string('extraqgraded', 'qcreate', $a);
            } else {
                $grammarised['extras'] = get_string('extraqsgraded', 'qcreate', $a);
            }
        }
        $punctuateds = $this->proper_punctuation($grammarised);
        return $punctuateds;
    }

    private function allowed_qtypes_list ($allowedqtypes) {
        if ($allowedqtypes != 'ALL') {
            $qtypesallowed = explode(',', $allowedqtypes);
        } else {
            $qtypesallowed = array_keys(qcreate::qtype_menu());
        }
        $allowedqtypelist = html_writer::start_tag('ul');
        foreach ($qtypesallowed as $qtypeallowed) {
            $allowedqtypelist .= '<li>' . question_bank::get_qtype_name($qtypeallowed) . '</li>';
        }
        $allowedqtypelist .= html_writer::end_tag('ul');
        return $allowedqtypelist;
    }

    /**
     * Render the teacher overview.
     *
     * @param qcreate_header $header
     * @return string
     */
    public function render_qcreate_teacher_overview(qcreate_teacher_overview $overview) {
        $o = '';
        $o .= $this->output->box_start('generalbox boxaligncenter', 'status');
        $o .= $this->output->container(format_string($this->time_status($overview->available, $overview->timenow,
                $overview->qcreate->timeopen, $overview->qcreate->timeclose)));
        $o .= $this->output->box_end();
        $o .= $this->output->box_start('generalbox boxaligncenter', 'grade');
        $o .= $this->output->container(format_string(get_string('totalgradeis', 'qcreate', $overview->qcreate->grade)));
        $o .= $this->output->box_end();
        $o .= $this->output->box_start('generalbox boxaligncenter', 'grading');
        $o .= $this->output->container(format_string($this->gradingstring($overview->qcreate->graderatio)));
        $o .= $this->output->box_end();
        $o .= $this->output->box_start('generalbox boxaligncenter boxwidthwide', 'questions_overview');
        $o .= $this->output->heading(get_string('requiredquestions', 'qcreate'), 3);
        $o .= html_writer::start_tag('ul');
        $requireds = $this->teacher_required_questions($overview->qcreate->requiredqtypes,
                                                   $overview->qcreate->totalrequired);
        if ($requireds) {
            foreach ($requireds as $key => $punctuated) {
                $o .= html_writer::start_tag('li') . $punctuated;
                if ($key == 'extras') {
                    $o .= $this->allowed_qtypes_list ($overview->qcreate->allowed);
                }
                $o .= html_writer::end_tag('li');
            }
        }
        $o .= $this->output->box_end();
        return $o;
    }

    /**
     * Render the  student view.
     *
     * @param qcreate_header $header
     * @return string
     */
    public function render_qcreate_student_view(qcreate_student_view $studentview) {
        $o = '';
        $o .= $this->output->box_start('generalbox boxaligncenter', 'status');
        $o .= $this->output->container(format_string($this->time_status($studentview->available, $studentview->timenow,
                $studentview->qcreate->timeopen, $studentview->qcreate->timeclose)));
        $o .= $this->output->box_end();
        $o .= $this->output->box_start('generalbox boxaligncenter boxwidthwide', 'required_questions');
        $o .= $this->output->heading(get_string('requiredquestions', 'qcreate'), 3);

        // Render required questions.
        $requireds = $studentview->qcreate->requiredqtypes;
        if ($requireds) {
            $grammarised = $this->proper_grammar($requireds);
            $punctuateds = $this->proper_punctuation($grammarised);
            $o .= html_writer::start_tag('ul', array('id' => 'requiredqlist'));
            foreach ($requireds as $qtype => $required) {
                $o .= $this->student_required_view($studentview->cm , $required, $studentview->cat, $punctuateds[$qtype],
                        $studentview->requiredquestions[$qtype], $studentview->qcreate->grade, $studentview->available);
            }
        }

        // Render extras questions.
        if ($studentview->extraquestionsgraded) {
            $a = new stdClass();
            $a->extraquestionsdone = $studentview->extraquestionsdone;
            $a->extrarequired = $studentview->extraquestionsgraded;
            $o .= html_writer::start_tag('li') . html_writer::start_tag('strong');
            if ($a->extraquestionsdone == 1) {
                $o .= get_string('extraqdone', 'qcreate', $a);
            } else {
                $o .= get_string('extraqsdone', 'qcreate', $a);
            }
            if ($a->extrarequired == 1) {
                $o .= '&nbsp;'.get_string('extraqgraded', 'qcreate', $a);
            } else {
                $o .= '&nbsp;'.get_string('extraqsgraded', 'qcreate', $a);
            }
            $o .= html_writer::end_tag('strong');
            $o .= html_writer::start_tag('ul');
            foreach ($studentview->qtypesallowed as $qtypeallowed) {
                $hasrequireds = isset($requireds[$qtypeallowed]);
                $o .= $this->student_extra_view($studentview->cm , $qtypeallowed, $hasrequireds,
                        $studentview->cat, $studentview->extraquestions[$qtypeallowed],
                        $studentview->qcreate->grade, $studentview->available);
            }
            $o .= html_writer::end_tag('ul');
            $o .= html_writer::end_tag('li');
        }

        // Render grade.
        $o .= $this->render_grade($studentview->studentgrade, $studentview->qcreate->graderatio);

        $o .= html_writer::end_tag('ul');
        $o .= $this->output->box_end();
        return $o;
    }

    public function student_required_view($cm, $required, $cat, $punctuated, $questions, $maxgrade, $available) {
        global $CFG;
        $o = '';
        $linklist = '';
        if ($available) {
            // One item list with one link to create question.
            $linklist .= html_writer::start_tag('ul') . html_writer::start_tag('li');
            $link = new moodle_url($CFG->wwwroot . '/question/question.php');
            $returnurl = new moodle_url($CFG->wwwroot . '/mod/qcreate/view.php', array('id' => $cm->id, 'qaction' => 'add'));
            $link->params(array('cmid' => $cm->id, 'qtype' => $required->qtype, 'category' => $cat,
                    'returnurl' => $returnurl->out_as_local_url(true)));
            if (count($questions)) {
                $linklist .= html_writer::link($link, get_string('clickhereanother', 'qcreate', $required->qtypestring));
            } else {
                $linklist .= html_writer::link($link, get_string('clickhere', 'qcreate', $required->qtypestring));
            }
            $linklist .= html_writer::end_tag('li') . html_writer::end_tag('ul');
        }

        if (count($questions)) {
            // Top level list.
            $questionlist = $this->student_questionlist_view($questions, $cm, $maxgrade, $available);
            $requirementslist = html_writer::start_tag('ul') . html_writer::start_tag('li');
            $requirementslist .= get_string('donequestionno', 'qcreate', $required);

            $requirementslist .= $questionlist . html_writer::end_tag('li');

            if ($required->stillrequiredno > 0) {
                $requirementslist .= html_writer::start_tag('li');
                $requirementslist .= get_string('todoquestionno', 'qcreate', $required);
                $requirementslist .= $linklist;
                $requirementslist .= html_writer::end_tag('li');
            }

            $requirementslist .= html_writer::end_tag('ul');
            $o .= html_writer::start_tag('li') .
                    $punctuated . $requirementslist . html_writer::end_tag('li');
        } else {
            $o .= html_writer::start_tag('li') .
                    $punctuated . $linklist . html_writer::end_tag('li');
        }
        return $o;
    }

    public function student_questionlist_view($questions, $cm, $maxgrade, $available) {
        $o = '';
        $o .= html_writer::start_tag('ul');
        foreach ($questions as $question) {
            $o .= html_writer::start_tag('li');
            $o .= $this->student_question_view($question, $cm, true, $maxgrade, $available);
            $o .= html_writer::end_tag('li');
        }
        $o .= html_writer::end_tag('ul');
        return $o;
    }

    public function student_question_view($question, $cm, $showgrades, $maxgrade, $available) {
        global $CFG;
        $o = '';
        $actionicons = '';
        if ($available && (question_has_capability_on($question, 'edit', $question->cid)
                || question_has_capability_on($question, 'move', $question->cid)
                || question_has_capability_on($question, 'view', $question->cid))) {
            $link = new moodle_url($CFG->wwwroot . '/question/question.php');

            $returnurl = new moodle_url($CFG->wwwroot . '/mod/qcreate/view.php', array('id' => $cm->id, 'qaction' => 'edit'));
            $link->params(array('cmid' => $cm->id, 'id' => $question->id, 'returnurl' => $returnurl->out_as_local_url(true)));
            $o .= html_writer::link($link, $question->name);
            $actionicons = '&nbsp;' . qcreate_question_action_icons($cm->id, $question, $returnurl);
        } else {
            $o = $question->name;
        }
        if ($showgrades) {
            $o .= '&nbsp;' . html_writer::start_tag('em');
            if ($question->gid && $question->grade != -1) {
                $o .= "({$question->grade}/{$maxgrade})";
            } else {
                $o .= "(".get_string('notgraded', 'qcreate').")";
            }
            if ($question->gradecomment != '') {
                $o .= '"'.$question->gradecomment.'"';
            }
            $o .= html_writer::end_tag('em');
        }

        $o .= $actionicons;

        return $o;
    }

    public function student_extra_view($cm, $qtypeallowed, $hasrequireds, $cat, $questions, $maxgrade, $available) {
        global $CFG;
        $o = '';
        $o .= html_writer::start_tag('ul') . html_writer::start_tag('li');
        if ($available) {
            // One item list with one link to create question.
            $link = new moodle_url($CFG->wwwroot . '/question/question.php');
            $returnurl = new moodle_url($CFG->wwwroot . '/mod/qcreate/view.php', array('id' => $cm->id, 'qaction' => 'add'));
            $link->params(array('cmid' => $cm->id, 'qtype' => $qtypeallowed, 'category' => $cat,
                    'returnurl' => $returnurl->out_as_local_url(true)));
            $o .= html_writer::link($link, question_bank::get_qtype_name($qtypeallowed));
        } else {
            $o .= question_bank::get_qtype_name($qtypeallowed);
        }
        $extrascount = count($questions);
        if ($hasrequireds && $extrascount) {
            if ($extrascount == 1) {
                $o .= '&nbsp;'.get_string('alreadydoneextraone', 'qcreate', $extrascount);
            } else {
                $o .= '&nbsp;'.get_string('alreadydoneextra', 'qcreate', $extrascount);
            }
        } else if ($extrascount) {
            if ($extrascount == 1) {
                $o .= '&nbsp;'.get_string('alreadydoneone', 'qcreate', $extrascount);
            } else {
                $o .= '&nbsp;'.get_string('alreadydone', 'qcreate', $extrascount);
            }
        }
        if ($extrascount) {
            $o .= html_writer::start_tag('ul');
            foreach ($questions as $question) {
                $o .= html_writer::start_tag('li');
                $o .= $this->student_question_view($question, $cm, true, $maxgrade, $available);
                $o .= html_writer::end_tag('li');
            }
            $o .= html_writer::end_tag('ul');
        }
        $o .= html_writer::end_tag('li') . html_writer::end_tag('ul');
        return $o;
    }

    public function render_grade($studentgrade, $graderatio) {
        $o = '';
        if (count((array)$studentgrade)) {
            $o .= html_writer::start_tag('li') . html_writer::start_tag('em');
            $o .= get_string('activitygrade', 'qcreate', $studentgrade->fullgrade);
            if (!empty($graderatio)) {
                $o .= html_writer::start_tag('ul');
                $o .= html_writer::tag('li',
                        html_writer::tag('em', get_string('automaticgrade', 'qcreate', $studentgrade->automaticgrade)));
                if ($graderatio != 100) {
                    $o .= html_writer::tag('li',
                            html_writer::tag('em', get_string('manualgrade', 'qcreate', $studentgrade->manualgrade)));
                }
                $o .= html_writer::end_tag('ul');
            }
            $o .= html_writer::end_tag('em') . html_writer::end_tag('li');
        }
        return $o;
    }
}

