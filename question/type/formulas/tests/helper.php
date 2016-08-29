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
 * Test helper code for the formulas question type.
 *
 * @package    qtype_formulas
 * @copyright  2012 Jean-Michel Védrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test helper class for the formulas question type.
 *
 * @copyright  2012 Jean-Michel Védrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_formulas_test_helper extends question_test_helper {
    const DEFAULT_CORRECT_FEEDBACK          = '<p>Correct answer, well done.</p>';
    const DEFAULT_PARTIALLYCORRECT_FEEDBACK = '<p>Your answer is partially correct.</p>';
    const DEFAULT_INCORRECT_FEEDBACK        = '<p>Incorrect answer.</p>';

    public function get_test_questions() {
        return array(
            'test0', // Minimal formulas question : one part, not randomised (answer = 5),
            'test1', // 3 parts, not randomised. (answers = 5, 6, 7),
            'test2', // 5 parts, separated and combined unit field,
            'test3'  // one part, not randomized, answer = 0 (to test problem with 0 as answer.
        );
    }

    /**
     * Does the basical initialisation of a new formulas question that all the test
     * questions will need.
     * @return qtype_formulas_question the new question.
     */
    protected static function make_a_formulas_question() {
        question_bank::load_question_definition_classes('formulas');

        $q = new qtype_formulas_question();
        test_question_maker::initialise_a_question($q);
        $q->qtype = question_bank::get_qtype('formulas');
        $q->contextid = context_system::instance()->id;
        $q->varsrandom = '';
        $q->varsglobal = '';
        $q->qv = new qtype_formulas_variables();
        $q->penalty = 0.2; // The default.
        test_question_maker::set_standard_combined_feedback_fields($q);
        $q->numpart = 0;   // This is of course invalid but should be changed by all tests.
        $q->parts = array();
        $q->evaluatedanswer = array();
        $q->fractions = array();
        $q->anscorrs = array();
        $q->unitcorrs = array();
        return $q;
    }

    protected static function make_a_formulas_part() {
        question_bank::load_question_definition_classes('formulas');

        $p = new qtype_formulas_part();
        $p->id = 0;
        $p->placeholder = '';
        $p->answermark = 1;
        $p->answertype = 0;
        $p->numbox = 1;
        $p->vars1 = '';
        $p->vars2 = '';
        $p->answer = '1';
        $p->correctness = '_relerr < 0.01';
        $p->unitpenalty = 1;
        $p->postunit = '';
        $p->ruleid = 1;
        $p->otherrule = '';
        $p->subqtext = '';
        $p->subqtextformat = 1;
        $p->feedback = '';
        $p->feedbackformat = 1;
        $p->partindex = 0;

        return $p;
    }

    /**
     * @return qtype_formulas_question the question from the test0.xml file.
     */
    public static function make_formulas_question_test0() {
        $q = self::make_a_formulas_question();

        $q->name = 'test-0';
        $q->questiontext = '<p>Minimal question : For a minimal question, you must define a part with (1) mark, (2) answer, (3) grading criteria, and optionally (4) question text.</p>';

        $q->penalty = 0.3; // Non-zero and not the default.
        $q->textfragments = array(0 => '<p>Minimal question : For a minimal question, you must define a part with (1) mark, (2) answer, (3) grading criteria, and optionally (4) question text.</p>',
                1 => '');
        $q->numpart = 1;
        $q->defaultmark = 2;
        $p = self::make_a_formulas_part();
        $p->id = 14;
        $p->answermark = 2;
        $p->answer = '5';
        $q->parts[0] = $p;

        return $q;
    }

    /**
     * @return qtype_formulas_question the question from the test1.xml file.
     * this version is non randomized to ease testing
     */
    public static function make_formulas_question_test1() {
        $q = self::make_a_formulas_question();

        $q->name = 'test-1';
        $q->questiontext = '<p>Multiple parts : By default, all parts will be added at the end. If placeholder is used, the part will be inserted at the location of placeholder.--{#1}--{#2}--{#3}</p>';
        $q->penalty = 0.3; // Non-zero and not the default.
        $q->textfragments = array(0 => '<p>Multiple parts : By default, all parts will be added at the end. If placeholder is used, the part will be inserted at the location of placeholder.--',
                1 => '--',
                2 => '--',
                3 => '</p>');
        $q->numpart = 3;
        $q->defaultmark = 6;
        $p0 = self::make_a_formulas_part();
        $p0->placeholder = '#1';
        $p0->id = 14;
        $p0->answermark = 2;
        $p0->answer = '5';
        $p0->subqtext = 'This is first part.';
        $q->parts[0] = $p0;
        $p1 = self::make_a_formulas_part();
        $p1->placeholder = '#2';
        $p1->id = 15;
        $p1->partindex = 1;
        $p1->answermark = 2;
        $p1->answer = '6';
        $p1->subqtext = 'This is second part.';
        $q->parts[1] = $p1;
        $p2 = self::make_a_formulas_part();
        $p2->placeholder = '#3';
        $p2->id = 16;
        $p2->partindex = 2;
        $p2->answermark = 2;
        $p2->answer = '7';
        $p2->subqtext = 'This is third part.';
        $q->parts[2] = $p2;

        return $q;
    }

    /**
     * @return qtype_formulas_question the question from the test1.xml file.
     */
    public static function make_formulas_question_test2() {
        $q = self::make_a_formulas_question();

        $q->name = 'test-2';
        $q->questiontext = '<p>This question shows different display methods of the answer and unit box.</p>';
        $q->penalty = 0.3; // Non-zero and not the default.
        $q->numpart = 4;
        $q->textfragments = array(0 => '<p>This question shows different display methods of the answer and unit box.</p>',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                );
        $q->defaultmark = 8;
        $q->varsrandom = 'v = {20:100:10}; dt = {2:6};';
        $q->varsglobal = 's = v*dt;';
        $p0 = self::make_a_formulas_part();
        $p0->id = 14;
        $p0->partindex = 0;
        $p0->answermark = 2;
        $p0->subqtext = '<p>If a car travel {s} m in {dt} s, what is the speed of the car? {_0}{_u}</p>';      // Combined unit.
        $p0->answer = 'v';
        $p0->postunit = 'm/s';
        $q->parts[0] = $p0;
        $p1 = self::make_a_formulas_part();
        $p1->id = 15;
        $p1->partindex = 1;
        $p1->answermark = 2;
        $p1->subqtext = '<p>If a car travel {s} m in {dt} s, what is the speed of the car? {_0} {_u}</p>';     // Separated unit.
        $p1->answer = 'v';
        $p1->postunit = 'm/s';
        $q->parts[1] = $p1;
        $p2 = self::make_a_formulas_part();
        $p2->id = 16;
        $p2->partindex = 2;
        $p2->answermark = 2;
        $p2->subqtext = '<p>If a car travel {s} m in {dt} s, what is the speed of the car? {_0} {_u}</p>';    // As postunit is empty {_u} should be ignored.
        $p2->answer = 'v';
        $p2->postunit = '';
        $q->parts[2] = $p2;
        $p3 = self::make_a_formulas_part();
        $p3->id = 17;
        $p3->partindex = 3;
        $p3->answermark = 2;
        $p3->subqtext = '<p>If a car travel {s} m in {dt} s, what is the speed of the car? speed = {_0}{_u}</p>';    // As postunit is empty {_u} should be ignored.
        $p3->answer = 'v';
        $p3->postunit = '';
        $q->parts[3] = $p3;

        return $q;
    }

    /**
     * @return qtype_formulas_question the question with 0 as answer.
     */
    public static function make_formulas_question_test3() {
        $q = self::make_a_formulas_question();

        $q->name = 'test-3';
        $q->questiontext = '<p>This question has 0.0 as answer to test problem when answer is equal to 0.</p>';

        $q->penalty = 0.3; // Non-zero and not the default.
        $q->textfragments = array(0 => '<p>This question has 0 as answer to test problem when answer is equal to 0.0.</p>',
                1 => '');
        $q->numpart = 1;
        $q->defaultmark = 2;
        $p = self::make_a_formulas_part();
        $p->id = 17;
        $p->answermark = 2;
        $p->answer = '0';
        $q->parts[0] = $p;

        return $q;
    }
}