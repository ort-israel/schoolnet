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
 * Functions for component 'local_searchquestions'
 *
 * @package   local_searchquestions
 * @copyright 2014 onwards Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');

/**
* Provide an array of search condition classes this plugin implements.
*
* @param \stdClass $caller
* @return core_question\bank\search\condition[]
*/
function local_searchquestions_get_question_bank_search_conditions($caller) {
    return array( new local_searchquestions_question_bank_search_condition($caller));
}

class local_searchquestions_question_bank_search_condition  extends core_question\bank\search\condition  {
    protected $tags;
    protected $where;
    protected $params;

    public function __construct() {
        $this->searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $this->searchanswers = optional_param('searchanswers', false, PARAM_BOOL);
        if (! empty($this->searchtext) ) {
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
        echo "<br />\n";
        echo html_writer::label(get_string('searchtext', 'local_searchquestions'), 'searchtext');
        echo html_writer::empty_tag('input', array('name' => 'searchtext', 'id' => 'searchtext', 'class' => 'searchoptions',
                'value' => $this->searchtext));
        echo "<br />\n";
        echo html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'searchanswers', 'id' => 'searchanswers',
                'class' => 'searchoptions', 'value' => 1));
        echo html_writer::label(get_string('searchanswers', 'local_searchquestions'), 'searchanswers');
    }

    private function init() {
        global $DB;
        $this->where = '(' . $DB->sql_like('questiontext', ':searchtext1', false) . ' OR ' .
                $DB->sql_like('q.name', ':searchtext2', false) . ')';
        $this->params['searchtext1'] = '%' . $DB->sql_like_escape($this->searchtext) . '%';
        $this->params['searchtext2'] = $this->params['searchtext1'];

        if ($this->searchanswers) {
            $this->where .= " OR ( q.id IN (SELECT question FROM {question_answers} qa WHERE " .
                    $DB->sql_like('answer', ':searchtext3', false) . ') )';
            $this->params['searchtext3'] = $this->params['searchtext1'];
        }
    }
}

