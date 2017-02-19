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
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');


function local_searchbytags_get_question_bank_search_conditions($caller) {
    return array(new local_searchbytags_question_bank_search_condition($caller));
}

function local_searchbytags_question_bank_column_types($questionbankview) {
    if ($questionbankview == 'quiz_question_bank_view') {
        return array();
    }
    return array('tags' => new local_searchbytags_question_bank_column($questionbankview));
}

class local_searchbytags_question_bank_search_condition extends core_question\bank\search\condition {
    protected $tags;
    protected $where;
    protected $params;

    public function __construct() {
        global $PAGE;
        global $CFG;
        if ($CFG->debug) {
            $PAGE->requires->yui_module('moodle-local_searchbytags-allowmultiple', 'M.local_searchbytags.allowmultiple.init');
        }
        $this->tags = optional_param_array('tags', array(), PARAM_TEXT);
        if ((!empty($this->tags)) && $this->tags[0] == null) {
            array_shift($this->tags);
        }
        $this->nottags = optional_param_array('nottags', array(), PARAM_TEXT);
        if ((!empty($this->nottags)) && $this->nottags[0] == null) {
            array_shift($this->nottags);
        }
        if ((!empty($this->tags)) || (!empty($this->nottags))) {
            $this->init();
        }
    }

    public function where() {
        return $this->where;
    }

    public function params() {
        return $this->params;
    }

    public function display_options_adv() {
        global $DB;
        global $output;
        require_login();

        $tags = $this->get_tags_used();
        $attr = array(
            'multiple' => 'true',
            'class' => 'searchoptions large searchbytags'
        );
        if (count($tags) > 10) {
            $attr['size'] = 10;
        }
        $strshowall= get_string('showall', 'local_searchbytags');
        echo html_writer::label(get_string('questionswithtags', 'local_searchbytags'), 'tags[]');
        echo "<br />\n";
        echo html_writer::select($tags, 'tags[]', $this->tags, array('' => $strshowall), $attr);
        echo "<br />\n";
        echo html_writer::label(get_string('questionswithouttags', 'local_searchbytags'), 'tags[]');
        echo "<br />\n";
        echo html_writer::select($tags, 'nottags[]', $this->nottags, array('' => $strshowall), $attr);
        echo "<br />\n";
    }

    private function init() {
        global $DB;

        $this->params = array();
        if (!empty($this->tags)) {
            if (!is_numeric($this->tags[0])) {
                list($tagswhere, $tagsparams) = $DB->get_in_or_equal($this->tags, SQL_PARAMS_NAMED, 'tag');
                $tagids = $DB->get_fieldset_select('tag', 'id', 'name ' . $tagswhere, $tagsparams);
            } else {
                $tagids = $this->tags;
            }
            list($where, $this->params) = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'tag');
            $this->where = "(SELECT COUNT(*) as tagcount FROM {tag_instance} ti WHERE itemid=q.id AND tagid $where)=" .
                count($this->tags);
        }

        if (!empty($this->nottags)) {
            if (!is_numeric($this->nottags[0])) {
                list($tagswhere, $tagsparams) = $DB->get_in_or_equal($this->nottags, SQL_PARAMS_NAMED, 'tag');
                $tagids = $DB->get_fieldset_select('tag', 'id', 'name ' . $tagswhere, $tagsparams);
            } else {
                $tagids = $this->nottags;
            }
            list($where, $params) = $DB->get_in_or_equal($tagids, SQL_PARAMS_NAMED, 'tag');
            if (!empty($this->where)) {
                $this->where .= " AND ";
            }
            $this->where .= "(SELECT COUNT(*) as tagcount FROM {tag_instance} ti WHERE itemid=q.id AND tagid $where)=0";
            $this->params = array_merge($this->params, $params);
        }
    }

    private function get_tags_used() {
        global $DB;
        $categories = $this->get_categories();
        list($catidtest, $params) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'cat');
        $sql = "SELECT name as value, name as display FROM {tag} WHERE id IN
                (
                 SELECT DISTINCT tagi.tagid FROM {tag_instance} tagi, {question}
                         WHERE itemtype='question' AND {question}.id=tagi.itemid AND category $catidtest
                )
                ORDER BY name";
        return $DB->get_records_sql_menu($sql, $params);
    }

    protected function get_current_category($categoryandcontext) {
        global $DB;
        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        if (!$categoryid) {
            return false;
        }

        if (!$category = $DB->get_record('question_categories',
            array('id' => $categoryid, 'contextid' => $contextid))
        ) {
            return false;
        }
        return $category;
    }

    private function get_categories() {
        $cmid = optional_param('cmid', 0, PARAM_INT);
        $categoryparam = optional_param('category', '', PARAM_TEXT);
        $courseid = optional_param('courseid', 0, PARAM_INT);

        if ($cmid) {
            list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars) = question_edit_setup('editq', '/mod/quiz/edit.php', true);
            if ($pagevars['cat']) {
                $categoryparam = $pagevars['cat'];
            }
        }

        if ($categoryparam) {
            $catandcontext = explode(',', $categoryparam);
            $cats = question_categorylist($catandcontext[0]);
            return $cats;
        } else if ($cmid) {
            list($module, $cm) = get_module_from_cmid($cmid);
            $courseid = $cm->course;
            require_login($courseid, false, $cm);
            $thiscontext = context_module::instance($cmid);
        } else {
            $module = null;
            $cm = null;
            if ($courseid) {
                $thiscontext = context_course::instance($courseid);
            } else {
                $thiscontext = null;
            }
        }

        $cats = get_categories_for_contexts($thiscontext->id);
        return array_keys($cats);
    }
}
