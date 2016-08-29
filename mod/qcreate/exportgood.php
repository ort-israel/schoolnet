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
 * Export questions in the given category and which have been assigned a grade
 * above a certain level.
 *
 * @package    mod_qcreate
 * @copyright  2008 Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/qcreate/export_good_questions_form.php');

list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) =
        question_edit_setup('export', '/mod/qcreate/exportgood.php', true);
$qcreate->cmidnumber = $cm->id;

if (!has_capability('moodle/question:viewmine', $contexts->lowest())
        && !has_capability('moodle/question:viewall', $contexts->lowest())) {
    $capabilityname = get_capability_string('moodle/question:viewmine');
    print_error('nopermissions', '', '', $capabilityname);
}

// Make sure we are using the user's most recent category choice.
if (empty($categoryid)) {
    $categoryid = $pagevars['cat'];
}

list($catid, $catcontext) = explode(',', $pagevars['cat']);
if (!$category = $DB->get_record("question_categories", array("id" => $catid, 'contextid' => $catcontext))) {
    print_error('nocategory', 'quiz');
}

// Header.
$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('exportquestions', 'qcreate'));
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

$exportfilename = question_default_export_filename($COURSE, $category);
$exportform = new qcreate_export_good_questions_form($thispageurl,
        array('contexts' => array($contexts->lowest()), 'defaultcategory' => $pagevars['cat'],
                                'defaultfilename' => $exportfilename, 'qcreate' => $qcreate));

if ($fromform = $exportform->get_data()) {   // Filename.
    $thiscontext = $contexts->lowest();

    if (! is_readable($CFG->dirroot."/question/format/$fromform->format/format.php")) {
        print_error('unknowformat', '', '', $fromform->format);;
    }
    $withcategories = 'nocategories';
    if (!empty($fromform->cattofile)) {
        $withcategories = 'withcategories';
    }
    $withcontexts = 'nocontexts';
    if (!empty($fromform->contexttofile)) {
        $withcontexts = 'withcontexts';
    }
    $betterthangrade = '0';
    if ($qcreate->graderatio != 100 && !empty($fromform->betterthangrade)) {
        $betterthangrade = $fromform->betterthangrade;
    }
    $naming = '';
    if (isset($fromform->naming['other'])&& !empty($fromform->naming['othertext'])) {
        $naming .= '1/' . $fromform->naming['othertext'] . '/';
    } else {
        $naming .= '0/none/';
    }
    $naming .= isset($fromform->naming['firstname']) ? '1/' : '0/';
    $naming .= isset($fromform->naming['lastname']) ? '1/' : '0/';
    $naming .= isset($fromform->naming['username']) ? '1/' : '0/';
    $naming .= isset($fromform->naming['activityname']) ? '1/' : '0/';
    $naming .= isset($fromform->naming['timecreated']) ? '1' : '0';
    // Load parent class for import/export.
    require_once($CFG->dirroot . '/question/format.php');

    // And then the class for the selected format.
    require_once($CFG->dirroot . "/question/format/$fromform->format/format.php");

    $classname = "qformat_$fromform->format";
    $qformat = new $classname();

    $filename = question_default_export_filename($COURSE, $category) .
            $qformat->export_file_extension();
    $urlbase = "$CFG->httpswwwroot/pluginfile.php";
    $exporturl = moodle_url::make_file_url($urlbase,
            "/{$thiscontext->id}/mod_qcreate/export/{$categoryid}/{$fromform->format}/{$withcategories}" .
            "/{$withcontexts}/{$betterthangrade}/{$naming}/{$filename}", true);

    echo $OUTPUT->box_start();
    echo get_string('yourfileshoulddownload', 'question', $exporturl->out());
    echo $OUTPUT->box_end();

    $PAGE->requires->js_function_call('document.location.replace', array($exporturl->out(false)), false, 1);

    echo $OUTPUT->continue_button(new moodle_url('edit.php', $thispageurl->params()));
    echo $OUTPUT->footer();
    exit;
}

// Display export form.
echo $OUTPUT->heading_with_help(get_string('exportquestions', 'qcreate'), 'exportquestions', 'question');

$exportform->display();

echo $OUTPUT->footer();
