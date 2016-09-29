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
 * Gapfill question definition class.
 *
 * @package    qtype
 * @subpackage gapfill
 * @copyright  2012 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class qtype_gapfill_question extends question_graded_automatically_with_countback {
    /* Not actually using the countback bit at the moment, not sure what it does.
     * if you are trying to make sense of Moodle question code, check the following link
     * http://docs.moodle.org/dev/Question_engine
     */

    public $answer;
    /* answerdisplay is a string of either gapfill,dropdown or drag drop */
    public $answerdisplay;
    public $wronganswers;
    public $shuffledanswers;
    public $correctfeedback;
    public $noduplicates;
    public $partiallycorrectfeedback = '';
    public $incorrectfeedback = '';
    public $correctfeedbackformat;
    public $partiallycorrectfeedbackformat;
    public $incorrectfeedbackformat;
    public $fraction;

    /* By default Cat is treated the same as cat, setting to 1 will make it case sensitive */
    public $casesensitive;

    /** @var array of question_answer. */
    public $answers = array();

    /* the characters indicating a field to fill i.e. [cat] creates
     * a field where the correct answer is cat
     */
    public $delimitchars = "[]";

    /**
     * @var array place number => group number of the places in the question
     * text where choices can be put. Places are numbered from 1.
     */
    public $places = array();

    /**
     * @var array of strings, one longer than $places, which is achieved by
     * indexing from 0. The bits of question text that go between the placeholders.
     */
    public $textfragments;

    /** @var array index of the right choice for each stem. */
    public $rightchoices;
    public $allanswers = array();

    public function start_attempt(question_attempt_step $step, $variant) {
        shuffle($this->allanswers);
        $step->set_qt_var('_allanswers', implode(',', $this->allanswers));
    }

    /**
     * @param int $key stem number
     * @return string the question-type variable name.
     */
    public function field($place) {
        return 'p' . $place;
    }

    public function get_expected_data() {
        /* it may make more sense to think of this as get expected data types */
        $data = array();
        foreach ($this->places as $key => $value) {
            $data['p' . $key] = PARAM_RAW_TRIMMED;
        }
        return $data;
    }

    /**
     * @param array $response  as might be passed to {@link grade_response()}
     * @return string 
     * Value returned will be written to responsesummary field of 
     * the question_attempts table
     */
    public function summarise_response(array $response) {
        $summary = "";
        foreach ($response as $key => $value) {
            $summary.=" " . $value . " ";
        }
        return $summary;
    }

    public function is_complete_response(array $response) {
        /* checks that none of of the gaps is blanks */
        foreach ($this->answers as $key => $value) {
            $ans = array_shift($response);
            if ($ans == "") {

                return false;
            }
        }
        return true;
    }

    public function get_validation_error(array $response) {
        if (!$this->is_gradable_response($response)) {
            return get_string('pleaseenterananswer', 'qtype_gapfill');
        }
    }

    /**
     * What is the correct value for the field 
     */
    public function get_right_choice_for($place) {
        return $this->places[$place];
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        /* if you are moving from viewing one question to another this will
         * discard the processing if the answer has not changed. If you don't
         * use this method it will constantantly generate new question steps and
         * the question will be repeatedly set to incomplete. This is a comparison of
         * the equality of two arrays.
         */
        if ($prevresponse == $newresponse) {
            return true;
        } else {
            return false;
        }
    }

    public function is_gradable_response(array $response) {
        /* are there any fields still left blank */
        return $this->is_complete_response($response);
    }

    /**
     * @return question_answer an answer that 
     * contains the a response that would get full marks.
     * used in preview mode
     */
    public function get_correct_response() {
        $response = array();
        foreach ($this->places as $place => $answer) {
            $response[$this->field($place)] = $answer;
        }
        return $response;
    }

    /* called from within renderer in interactive mode */

    public function is_correct_response($answergiven, $rightanswer) {
        if (!$this->casesensitive == 1) {
            $answergiven = strtolower($answergiven);
            $rightanswer = strtolower($rightanswer);
        }
        if ($this->compare_string_with_wildcard($answergiven, $rightanswer, $this->casesensitive)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param array $response Passed in from the submitted form
     * @return array 
     *
     * Find count of correct answers, used for displaying marks
     * for question. Compares answergiven with right/correct answer
     */
    public function get_num_parts_right(array $response) {
        $numright = 0;
        foreach ($this->places as $place => $notused) {
            if (!array_key_exists($this->field($place), $response)) {
                continue;
            }
            $answergiven = $response[$this->field($place)];
            $rightanswer = $this->get_right_choice_for($place);
            if (!$this->casesensitive == 1) {
                $answergiven = strtolower($answergiven);
                $rightanswer = strtolower($rightanswer);
            }
            if ($this->compare_string_with_wildcard($answergiven, $rightanswer, $this->casesensitive)) {
                $numright+=1;
            }
        }
        return array($numright, count($this->places));
    }

    /**
     * Given a response, rest the parts that are wrong. Relevent in 
     * interactive with multiple tries
     * @param array $response a response
     * @return array a cleaned up response with the wrong bits reset.
     */
    public function clear_wrong_from_response(array $response) {
        foreach ($this->places as $place => $notused) {
            if (!array_key_exists($this->field($place), $response)) {
                continue;
            }
            $answergiven = $response[$this->field($place)];
            $rightanswer = $this->get_right_choice_for($place);
            if (!$this->casesensitive == 1) {
                $answergiven = strtolower($answergiven);
                $rightanswer = strtolower($rightanswer);
            }
            if (!$this->compare_string_with_wildcard($answergiven, $rightanswer, $this->casesensitive)) {
                $response[$this->field($place)] = '';
            }
        }
        return $response;
    }

    public function discard_duplicates(array $response) {
        if ($this->noduplicates == 1) {
            /*
             * find unique values then keeping the same
             * keys blank rest of the values
             */
            $au = array_unique($response);
            foreach ($response as $key => $value) {
                $response[$key] = '';
            }
            return array_merge($response, $au);
        } else {
            return $response;
        }
    }

    public function grade_response(array $response) {
        $response = $this->discard_duplicates($response);
        list($right, $total) = $this->get_num_parts_right($response);
        $this->fraction = $right / $total;
        $grade = array($this->fraction, question_state::graded_state_for_fraction($this->fraction));
        return $grade;
    }

    // Required by the interface question_automatically_gradable_with_countback.
    public function compute_final_grade($responses, $totaltries) {
        // Only applies in interactive mode.
        $responses[0] = $this->discard_duplicates($responses[0]);
        $totalscore = 0;
        foreach ($this->places as $place => $notused) {
            $fieldname = $this->field($place);
            $lastwrongindex = -1;
            $finallyright = false;
            foreach ($responses as $i => $response) {
                $rcfp = $this->get_right_choice_for($place);
                /* break out the loop if response does not contain the key */
                if (!array_key_exists($fieldname, $response)) {
                    continue;
                }
                $resp = $response[$fieldname];
                if (!$this->compare_string_with_wildcard($resp, $rcfp, $this->casesensitive)) {
                    $lastwrongindex = $i;
                    $finallyright = false;
                } else {
                    $finallyright = true;
                }
            }
            if ($finallyright) {
                $totalscore += max(0, 1 - ($lastwrongindex + 1) * $this->penalty);
            }
        }
        return $totalscore / count($this->places);
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && in_array($filearea, array('correctfeedback',
                    'partiallycorrectfeedback', 'incorrectfeedback'))) {
            return $this->check_combined_feedback_file_access($qa, $options, $filearea);
        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }

    /* borrowed directly from the shortanswer question */

    public function compare_string_with_wildcard($string, $pattern, $casesensitive) {
        /* answers with a positive grade must be anchored for strict match
          incorrect answers are not strictly matched */

        /* If you want to escape all wildcards the following code
         * will do it. But I default to leaving them into allow
         * their use as part of the quetion. I escape forward slash
         * because I create html questions with tag close match
         * i.e. [/div]
         * $pattern =preg_quote($pattern,'/');
         */
        $pattern = str_replace('/', '\/', $pattern);

        $regexp = '/^' . $pattern . '$/u';

        // Make the match insensitive if requested to.
        if (!$casesensitive) {
            $regexp .= 'i';
        }
        if (preg_match($regexp, trim($string))) {
            return true;
        }
    }

    public function get_marked_gaps(question_attempt $qa, question_display_options $options) {
        $marked_gaps = array();
        $question = $qa->get_question();
        $correct_gaps = array();
        foreach ($question->textfragments as $place => $fragment) {
            if ($place < 1) {
                continue;
            }
            $fieldname = $question->field($place);
            $rightanswer = $question->get_right_choice_for($place);
            if (($options->correctness) or ($options->numpartscorrect)) {
                $response = $qa->get_last_qt_data();

                if (array_key_exists($fieldname, $response)) {
                    if ($question->is_correct_response($response[$fieldname], $rightanswer)) {
                        $marked_gaps[$fieldname]['value'] = $response[$fieldname];
                        $marked_gaps[$fieldname]['fraction'] = 1;
                        $correct_gaps[] = $response[$fieldname];
                    } else {
                        $marked_gaps[$fieldname]['value'] = $response[$fieldname];
                        $marked_gaps[$fieldname]['fraction'] = 0;
                    }
                }
            }
        }
        $arr_unique = array_unique($correct_gaps);
        $arr_duplicates = array_diff_assoc($correct_gaps, $arr_unique);
        foreach ($marked_gaps as $fieldname => $gap) {

            if (in_array($gap['value'], $arr_duplicates)) {
                $marked_gaps[$fieldname]['duplicate'] = 'true';
            } else {
                $marked_gaps[$fieldname]['duplicate'] = 'false';
            }
        }
        return $marked_gaps;
    }

}
