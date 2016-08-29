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
 * Page to grade created questions.
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/qcreate/lib.php');
require_once($CFG->dirroot . '/question/editlib.php');

list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) = question_edit_setup('questions', '/mod/qcreate/edit.php');
$qcreate->cmidnumber = $cm->id;
require_capability('mod/qcreate:grade', context_module::instance($cm->id));
if ($qcreate->graderatio == 100) {
    $gradinginterface = false;
} else {
    $gradinginterface = true;
}

$page    = optional_param('page', 0, PARAM_INT);
$gradessubmitted   = optional_param('gradessubmitted', 0, PARAM_BOOL);          // Grades submitted?

if ($gradinginterface) {
    $showungraded = optional_param('showungraded', 1, PARAM_BOOL);
    $showgraded = optional_param('showgraded', 1, PARAM_BOOL);
    $showneedsregrade = optional_param('showneedsregrade', 1, PARAM_BOOL);
} else {
    $showungraded = true;
    $showgraded = true;
    $showneedsregrade = true;
}



/* first we check to see if the form has just been submitted
 * to request user_preference updates
 */
$updatepref = optional_param('updatepref', 0, PARAM_INT);
if ($updatepref) {
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $perpage = ($perpage <= 0) ? QCREATE_PER_PAGE : $perpage;
    $perpage = ($perpage > QCREATE_MAX_PER_PAGE) ? QCREATE_MAX_PER_PAGE : $perpage;
    set_user_preference('qcreate_perpage', $perpage);
}

// Find out current groups mode.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

// Get all ppl that are allowed to submit grades.
$context = context_module::instance($cm->id);
if (!$users = get_users_by_capability($context, 'mod/qcreate:submit', '', '', '', '', $currentgroup, '', false)) {
    $users = array();
}

$users = array_keys($users);
if (!empty($CFG->enablegroupings) && !empty($cm->groupingid)) {
    $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id');
    $users = array_intersect($users, array_keys($groupingusers));

}
// Grades submitted?
if ($gradessubmitted) {
    qcreate_process_grades($qcreate, $cm, $users);
}

/* next we get perpage params
 * from database
 */
$perpage    = get_user_preferences('qcreate_perpage', 10);

$gradinginfo = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id);

if (!empty($CFG->enableoutcomes) and !empty($gradinginfo->outcomes)) {
    $usesoutcomes = true;
} else {
    $usesoutcomes = false;
}

$teacherattempts = true; // Temporary measure.
$strsaveallfeedback = get_string('saveallfeedback', 'qcreate');


$tabindex = 1; // Tabindex for quick grading tabbing; Not working for dropdowns yet.

// Log this visit.
$params = array(
    'courseid' => $COURSE->id,
    'context' => $context,
    'other' => array(
        'qcreateid' => $qcreate->id
    )
);
$event = \mod_qcreate\event\edit_page_viewed::create($params);
$event->trigger();

$strqcreate = get_string('modulename', 'qcreate');
$strqcreates = get_string('modulenameplural', 'qcreate');

$PAGE->set_url($thispageurl);

// Prepare header.
$title = $COURSE->shortname . ': ' . format_string($qcreate->name);
$PAGE->set_title($title);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading($qcreate->name, 2, null);

$thispageurl->params(compact('showgraded', 'showneedsregrade', 'showungraded', 'page'));

groups_print_activity_menu($cm, $thispageurl->out());

if ($gradinginterface) {
    $tablecolumns = array('picture', 'fullname', 'qname', 'grade', 'status',
            'gradecomment', 'timemodified', 'timemarked', 'finalgrade');
    $tableheaders = array('',
                          get_string('fullname'),
                          get_string('question'),
                          get_string('grade'),
                          get_string('status'),
                          get_string('comment', 'qcreate'),
                          get_string('lastmodified'),
                          get_string('marked', 'qcreate'),
                          get_string('finalgrade', 'grades'));
    if ($usesoutcomes) {
        $tablecolumns[] = 'outcomes'; // No sorting based on outcomes column.
        $tableheaders[] = get_string('outcomes', 'grades');
    }
} else {
    $tablecolumns = array('picture', 'fullname', 'qname', 'gradecomment', 'timemodified', 'finalgrade');
    $tableheaders = array('',
                          get_string('fullname'),
                          get_string('question'),
                          get_string('comment', 'qcreate'),
                          get_string('lastmodified'),
                          get_string('finalgrade', 'grades'));
}

$table = new flexible_table('mod-qcreate-grades');

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($thispageurl->out());

$table->sortable(true, 'lastname');// Sorted by lastname by default.
$table->collapsible(true);
$table->initialbars(true);

$table->column_suppress('picture');
$table->column_suppress('fullname');

$table->column_class('picture', 'picture');
$table->column_class('fullname', 'fullname');
$table->column_class('question', 'question');
$table->column_class('gradecomment', 'comment');
$table->column_class('timemodified', 'timemodified');
$table->column_class('finalgrade', 'finalgrade');
if ($gradinginterface) {
    $table->column_class('grade', 'grade');
    $table->column_class('timemarked', 'timemarked');
    $table->column_class('status', 'status');
    if ($usesoutcomes) {
        $table->column_class('outcomes', 'outcome');
    }
}

$table->set_attribute('id', 'attempts');
$table->set_attribute('class', 'grades');

if ($gradinginterface) {
    $table->no_sorting('finalgrade');
    $table->no_sorting('outcomes');
}
// Start working -- this is necessary as soon as the niceties are over.
$table->setup();



// Construct the SQL.


if (!empty($users) && ($showungraded || $showgraded || $showneedsregrade)) {
    if ($sort = $table->get_sql_sort()) {
        $sort = ' ORDER BY '.$sort;
    }

    $where = $table->get_sql_where();
    if ($where[0]) {
        $where[0] .= ' AND ';
    }

    // Unfortunately we cannot use status in WHERE clause.
    switch ($showungraded . $showneedsregrade . $showgraded) {
        case '001':
            $where[0] .= '(g.timemarked IS NOT NULL) AND (g.timemarked >= q.timemodified ) AND ';
            break;
        case '010':
            $where[0] .= '(g.timemarked IS NOT NULL) AND (g.timemarked < q.timemodified ) AND ';
            break;
        case '011':
            $where[0] .= '(g.timemarked IS NOT NULL) AND ';
            break;
        case '100':
            $where[0] .= '(g.timemarked IS NULL) AND ';
            break;
        case '101':
            $where[0] .= '((g.timemarked IS NULL) OR g.timemarked >= q.timemodified) AND ';
            break;
        case '110':
            $where[0] .= '((g.timemarked IS NULL) OR g.timemarked < q.timemodified) AND ';
            break;
        case '111': // Show everything.
            break;
    }
    if ($qcreate->allowed != 'ALL') {
        $allowedparts = explode(',', $qcreate->allowed);
        $allowedlist = "'".join("','", $allowedparts)."'";
        $where[0] .= 'q.qtype IN ('.$allowedlist.') AND ';
    }

    $countsql = 'SELECT COUNT(*) FROM {user} u, {question_categories} c, {question} q '.
           'LEFT JOIN {qcreate_grades} g ON q.id = g.questionid '.
           'WHERE ' . $where[0] . 'q.createdby = u.id AND u.id IN (' . implode(',', $users) .
            ') AND q.hidden=\'0\' AND q.parent=\'0\' AND q.category = c.id and c.contextid='.$context->id;
    $answercount = $DB->count_records_sql($countsql, $where[1]);

    $ufields = user_picture::fields('u');
    // Complicated status calculation is needed for sorting on status column.
    $select = "SELECT q.id AS qid, $ufields,
                      g.id AS gradeid, g.grade, g.gradecomment,
                      q.timemodified, g.timemarked,
                      q.qtype, q.name AS qname,
                      COALESCE(
                        SIGN(SIGN(g.timemarked) + SIGN(g.timemarked - q.timemodified))
                        ,-1
                      ) AS status ";
    $sql = 'FROM {user} u, {question_categories} c, {question} q '.
           'LEFT JOIN {qcreate_grades} g ON q.id = g.questionid
                                                              AND g.qcreateid = '.$qcreate->id.' '.
           'WHERE ' . $where[0] . 'q.createdby = u.id AND u.id IN (' . implode(',', $users) .
            ') AND q.hidden=\'0\' AND q.parent=\'0\' AND q.category = c.id and c.contextid='.$context->id;
} else {
    $answercount = 0;
}

if ($gradinginterface) {
    echo '<form id="showoptions" action="'.$thispageurl->out(true).'" method="post">';
    echo '<div>';

    // Default value for checkbox when checkbox not checked.

    echo '<input type="hidden" name="showgraded" value="0" />';
    echo '<input type="hidden" name="showneedsregrade" value="0" />';
    echo '<input type="hidden" name="showungraded" value="0" />';
    echo '</div>';
    echo '<div class="mdl-align">';
    print_string('show', 'qcreate');
    $checked = $showgraded ? ' checked="checked"' : '';
    echo '<input onchange="getElementById(\'showoptions\').submit(); return true;"' .
            ' type="checkbox" value="1" name="showgraded" id="id_showgraded"' . $checked.'/>';
    echo '<label for="id_showgraded">'.get_string('showgraded', 'qcreate').'</label>';
    $checked = $showneedsregrade ? ' checked="checked"' : '';
    echo '&nbsp;<input onchange="getElementById(\'showoptions\').submit(); return true;"' .
            ' type="checkbox" value="1" name="showneedsregrade" id="id_showneedsregrade"'.$checked.'/>';
    echo '<label for="id_showneedsregrade">'.get_string('showneedsregrade', 'qcreate').'</label>';
    $checked = $showungraded ? ' checked="checked"' : '';
    echo '&nbsp;<input onchange="getElementById(\'showoptions\').submit(); return true;"' .
            ' type="checkbox" value="1" name="showungraded" id="id_showungraded"'.$checked.'/>';
    echo '<label for="id_showungraded">'.get_string('showungraded', 'qcreate').'</label>';
    echo '<noscript>';
    echo '<input type="submit" name="go" value="'.get_string('go').'" />';
    echo '</noscript>';
    echo '</div></form>';
}
$table->pagesize($perpage, $answercount);

$tablehasdata = false;

ob_start();
if ($answercount && false !== ($answers = $DB->get_records_sql(
        $select.$sql.$sort, $where[1], $table->get_page_start(), $table->get_page_size()
        ))) {
    $strupdate = get_string('update');
    $strgrade  = get_string('grade');
    $grademenu = make_grades_menu($qcreate->grade);
    $gradinginfo = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id, $users);

    foreach ($answers as $answer) {
        $finalgradevalue = $gradinginfo->items[0]->grades[$answer->id];
        // Calculate user status.
        $answer->needsregrading = ($answer->timemarked <= $answer->timemodified);

        $answer->imagealt = fullname($answer);
        $picture = $OUTPUT->user_picture($answer, array('courseid' => $COURSE->id));

        if (empty($answer->gradeid)) {
            $answer->grade = -1; // No grade yet.
        }
        if ($gradinginterface && !$answer->needsregrading && $answer->timemarked != 0) {
            $highlight = true;
        } else {
            $highlight = false;
        }
        $colquestion = $answer->qname;
        // Preview?
        $strpreview = get_string('preview', 'qcreate');

        if (question_has_capability_on($answer->qid, 'use')) {

            $link = new moodle_url('/question/preview.php?id=' . $answer->qid . '&amp;courseid=' .$COURSE->id);
            $colquestion .= $OUTPUT->action_link($link, "<img src=\""
                    .$OUTPUT->pix_url('t/preview')."\" class=\"iconsmall\" alt=\"$strpreview\" />",
                    new popup_action ('click', $link, 'questionpreview', question_preview_popup_params()));
        }

        // Edit, hide, delete question, using question capabilities, not quiz capabilieies.
        if (question_has_capability_on($answer->qid, 'edit') || question_has_capability_on($answer->qid, 'move')) {
            $questionparams = array('returnurl' => $thispageurl->out_as_local_url(), 'cmid' => $cm->id, 'id' => $answer->qid);
            $link = new moodle_url('/question/question.php', $questionparams);
            $colquestion .= html_writer::link(
                $link, $OUTPUT->pix_icon('t/edit', get_string('edit'), '', array('class' => 'iconsmall')));
        } else if (question_has_capability_on($answer->qid, 'view')) {
            $questionparams = array('returnurl' => $thispageurl->out_as_local_url(), 'cmid' => $cm->id, 'id' => $answer->qid);
            $link = new moodle_url('/question/question.php', $questionparams);
            $colquestion .= html_writer::link(
                $link, $OUTPUT->pix_icon('t/view', get_string('view'), '', array('class' => 'iconsmall')));
        }

        if ($highlight) {
            $colquestion = '<span class="highlight">'.$colquestion.'</span>';
        }
        $colquestion .= '<br />(' . question_bank::get_qtype_name($answer->qtype) . ')';
        if ($answer->timemodified > 0) {
            $studentmodified = '<div id="ts'.$answer->qid.'">'.userdate($answer->timemodified).'</div>';
        } else {
            $studentmodified = '';
        }
        if (!empty($answer->gradeid)) {
            // Prints student answer and student modified date
            // attach file or print link to student answer, depending on the type of the assignment.
            // Refer to print_student_answer in inherited classes.

            // Print grade, dropdown or text.
            if ($answer->timemarked > 0) {
                $teachermodified = '<div id="tt'.$answer->qid.'">'.userdate($answer->timemarked).'</div>';

                if ($finalgradevalue->locked or $finalgradevalue->overridden) {
                    $grade = '<div id="g'.$answer->qid.'">'.$finalgradevalue->str_grade.'</div>';
                } else {
                    $menu = html_writer::select(make_grades_menu($qcreate->grade),
                            'menu['.$answer->qid.']', $answer->grade, array('-1' => get_string('nograde')));
                    $grade = '<div id="g'.$answer->qid.'">'. $menu .'</div>';
                }

            } else {
                $teachermodified = '<div id="tt'.$answer->qid.'">&nbsp;</div>';
                if ($finalgradevalue->locked or $finalgradevalue->overridden) {
                    $grade = '<div id="g'.$answer->qid.'">'.$finalgradevalue->str_grade.'</div>';
                } else {
                    $menu = html_writer::select(make_grades_menu($qcreate->grade),
                            'menu['.$answer->qid.']', $answer->grade, array('-1' => get_string('nograde')));
                    $grade = '<div id="g'.$answer->qid.'">'.$menu.'</div>';
                }
            }
            // Print Comment.
            if ($finalgradevalue->locked or $finalgradevalue->overridden) {
                $comment = '<div id="com'.$answer->qid.'">'.shorten_text(strip_tags($finalgradevalue->str_feedback), 15).'</div>';

            } else {
                $comment = '<div id="com'.$answer->qid.'">'
                         . '<textarea tabindex="'.$tabindex++.'" name="gradecomment['.$answer->qid.']" id="gradecomment'
                         . $answer->qid.'" rows="4" cols="30">'.($answer->gradecomment).'</textarea></div>';
            }
        } else {
            $teachermodified = '<div id="tt'.$answer->qid.'">&nbsp;</div>';
            $status          = '<div id="st'.$answer->qid.'">&nbsp;</div>';

            if ($finalgradevalue->locked or $finalgradevalue->overridden) {
                $grade = '<div id="g'.$answer->qid.'">'.$finalgradevalue->str_grade.'</div>';
            } else {   // Allow editing.
                $menu = html_writer::select(make_grades_menu($qcreate->grade),
                       'menu['.$answer->qid.']', $answer->grade, array('-1' => get_string('nograde')));
                $grade = '<div id="g'.$answer->qid.'">'.$menu.'</div>';
            }

            if ($finalgradevalue->locked or $finalgradevalue->overridden) {
                $comment = '<div id="com'.$answer->qid.'">'.$finalgradevalue->str_feedback.'</div>';
            } else {
                $comment = '<div id="com'.$answer->qid.'">'
                         . '<textarea tabindex="'.$tabindex++.'" name="gradecomment['.$answer->qid.']" id="gradecomment'
                         . $answer->qid.'" rows="4" cols="30">'.($answer->gradecomment).'</textarea></div>';
            }
        }

        if ($answer->timemarked == 0) {
            $status = get_string('needsgrading', 'qcreate');
        } else if ($answer->needsregrading) {
            $status = get_string('needsregrading', 'qcreate');
        } else {
            $status = get_string('graded', 'qcreate');
        }
        if ($highlight) {
            $status = '<span class="highlight">'.$status.'</span>';
        }

        $finalgradestr = '<span id="finalgrade_'.$answer->qid.'">'.$finalgradevalue->str_grade.'</span>';

        $outcomes = '';

        if ($usesoutcomes) {
            foreach ($gradinginfo->outcomes as $index => $outcome) {
                $outcomes .= html_writer::start_tag('div', array('class' => 'outcome')). html_writer::tag('label', $outcome->name );
                $options = make_grades_menu(-$outcome->scaleid);

                if ($outcome->grades[$answer->id]->locked) {
                    $options[0] = get_string('nooutcome', 'grades');
                    $outcomes .= ': ' . html_writer::tag('span', $options[$outcome->grades[$answer->qid]->grade],
                            array(id => "outcome_'.$index.'_'.$answer->qid.'"));
                } else {
                    $outcomes .= ' ';
                    $outcomes .= html_writer::select($options, 'outcome_'.$index.'['.$answer->qid.']',
                                $outcome->grades[$answer->qid]->grade, get_string('nooutcome', 'grades'),
                                        array('id' => 'outcome_'.$index.'_'.$answer->qid));
                }
                $outcomes .= html_writer::end_tag('div');
            }
        }

        if ($gradinginterface) {
            $row = array($picture, fullname($answer), $colquestion, $grade, $status, $comment, $studentmodified,
                    $teachermodified, $finalgradestr);
        } else {
            $row = array($picture, fullname($answer), $colquestion, $comment, $studentmodified, $finalgradestr);
        }
        if ($usesoutcomes) {
            $row[] = $outcomes;
        }

        $table->add_data($row);
        $tablehasdata = true;
    }
}

$table->finish_html();  // Print the whole table.
$tableoutput = ob_get_clean();

if ($tablehasdata) {
    echo '<form action="'.$thispageurl->out(true).'" method="post">';
    echo '<div>';
    echo '<input type="hidden" name="gradessubmitted" value="1" />';
    echo '</div>';
}

echo $tableoutput;

if ($tablehasdata) {
    if ($gradinginterface) {
        echo '<div style="text-align:center"><input type="submit" name="fastg" value="' .
                get_string('saveallfeedbackandgrades', 'qcreate').'" /></div>';
    } else {
        echo '<div style="text-align:center"><input type="submit" name="fastg" value="' .
                get_string('saveallfeedback', 'qcreate').'" /></div>';
    }
    echo '</form>';
    // End of fast grading form.
}

// Mini form for setting user preference.
echo "\n<br />";

$form = '<form id="options" action="'.$thispageurl->out(true).'" method="post">';
$form .= '<fieldset class="invisiblefieldset">';
$form .= '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
$form .= '<p>';
$form .= '<label for="id_perpage">'.get_string('pagesize', 'qcreate').'</label>';
$form .= '<input type="text" id="id_perpage" name="perpage" size="1" value="'.$perpage.'" /><br />';
$form .= '<input type="submit" value="'.get_string('savepreferences').'" />';
$form .= '</p>';
$form .= '</fieldset>';
$form .= '</form>';
echo $OUTPUT->box($form, 'generalbox boxaligncenter boxwidthnarrow');
// End of mini form.

echo $OUTPUT->footer();
