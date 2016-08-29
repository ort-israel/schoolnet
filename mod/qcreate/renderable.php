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
 * This file contains the definition for the renderable classes for the qcreatement
 *
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable course index summary
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qcreate_course_index_summary implements renderable {
    /** @var array qcreates - A list of course module info and question counts */
    public $qcreates = array();
    /** @var boolean usesections - Does this course format support sections? */
    public $usesections = false;
    /** @var string courseformat - The current course format name */
    public $courseformatname = '';

    /**
     * constructor
     *
     * @param boolean $usesections - True if this course format uses sections
     * @param string $courseformatname - The id of this course format
     */
    public function __construct($usesections, $courseformatname) {
        $this->usesections = $usesections;
        $this->courseformatname = $courseformatname;
    }

    /**
     * Add a row of data to display on the course index page
     *
     * @param int $cmid - The course module id for generating a link
     * @param string $cmname - The course module name for generating a link
     * @param string $sectionname - The name of the course section (only if $usesections is true)
     * @param int $timeopen - The due date for the qcreate - may be 0 if no timeopen
     * @param int $timeclose - The due date for the qcreate - may be 0 if no timeclose
     * @param int $questions - Number of created questions (for user or for all users)
     * @param string $gradeinfo - The current users grade if they have been graded and it is not hidden.
     */
    public function add_qcreate_info($cmid, $cmname, $sectionname, $timeopen, $timeclose, $questions, $gradeinfo) {
        $this->qcreates[] = array('cmid' => $cmid,
                               'cmname' => $cmname,
                               'sectionname' => $sectionname,
                               'timeopen' => $timeopen,
                               'timeclose' => $timeclose,
                               'questions' => $questions,
                               'gradeinfo' => $gradeinfo);
    }
}

/**
 * Renderable header
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qcreate_header implements renderable {
    /** @var stdClass the qcreate record  */
    public $qcreate = null;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;
    /** @var string $subpage optional subpage (extra level in the breadcrumbs) */
    public $subpage = '';
    /** @var string $preface optional preface (text to show before the heading) */
    public $preface = '';

    /**
     * Constructor
     *
     * @param stdClass $qcreate  - the qcreate database record
     * @param mixed $context context|null the course module context
     * @param bool $showintro  - show or hide the intro
     * @param int $coursemoduleid  - the course module id
     * @param string $subpage  - an optional sub page in the navigation
     * @param string $preface  - an optional preface to show before the heading
     */
    public function __construct(stdClass $qcreate,
                                $context,
                                $showintro,
                                $coursemoduleid,
                                $subpage='',
                                $preface='') {
        $this->qcreate = $qcreate;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->subpage = $subpage;
        $this->preface = $preface;
    }
}

/**
 * Renderable overview
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qcreate_teacher_overview implements renderable {
    /** @var stdClass the qcreate record  */
    public $qcreate = null;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;
    public $available;
    public $timenow;
    public $required;
    public $allowed;

    /**
     * Constructor
     *
     * @param stdClass $qcreate  - the qcreate database record
     * @param mixed $context context|null the course module context
     * @param int $coursemoduleid  - the course module id
     */
    public function __construct(stdClass $qcreate,
                                $context,
                                $coursemoduleid,
                                $available) {
        $this->qcreate = $qcreate;
        $this->context = $context;
        $this->coursemoduleid = $coursemoduleid;
        $this->available = $available;
        $this->timenow = time();
    }
}

/**
 * Renderable view page
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qcreate_student_view implements renderable {
    /** @var stdClass the qcreate record  */
    public $qcreate = null;
    public $cm;
    public $cat;
    public $requiredquestions;
    public $extraquestions;
    public $qtyperequired;
    public $extraquestionsgraded;
    public $extraquestionsdone;
    public $extras;
    public $studentgrade;
    public $qtypesallowed;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;
    public $available;
    public $timenow;

    /**
     * Constructor
     *
     * @param stdClass $qcreate  - the qcreate database record
     * @param mixed $context context|null the course module context
     * @param int $coursemoduleid  - the course module id
     */
    public function __construct(stdClass $qcreate,
                                $cm,
                                $cat,
                                $requiredquestions,
                                $extraquestions,
                                $qtyperequired,
                                $extraquestionsgraded,
                                $extraquestionsdone,
                                $extras,
                                $studentgrade,
                                $qtypesallowed,
                                $context,
                                $coursemoduleid,
                                $available) {
        $this->qcreate = $qcreate;
        $this->cm = $cm;
        $this->cat = $cat;
        $this->requiredquestions = $requiredquestions;
        $this->extraquestions = $extraquestions;
        $this->extraquestionsgraded = $extraquestionsgraded;
        $this->extraquestionsdone = $extraquestionsdone;
        $this->extras = $extras;
        $this->studentgrade = $studentgrade;
        $this->qtypesallowed = $qtypesallowed;
        $this->context = $context;
        $this->coursemoduleid = $coursemoduleid;
        $this->available = $available;
        $this->timenow = time();
    }
}