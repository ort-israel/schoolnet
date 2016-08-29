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

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/qcreate/renderable.php');


/**
 * Standard base class for mod_qcreate.
 *
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qcreate {

    /** @var stdClass the qcreate record that contains the global settings for this qcreate instance */
    private $instance;

    /** @var stdClass the grade_item record for this qcreate instance's primary grade item. */
    private $gradeitem;

    /** @var context the context of the course module for this qcreate instance
     *               (or just the course if we are creating a new one)
     */
    private $context;

    /** @var stdClass the course this qcreate instance belongs to */
    private $course;

    /** @var stdClass the admin config for all qcreate instances  */
    private $adminconfig;

    /** @var qcreate_renderer the custom renderer for this module */
    private $output;

    /** @var stdClass the course module for this qcreate instance */
    private $coursemodule;

    /** @var array cache for things like the coursemodule name or the scale menu -
     *             only lives for a single request.
     */
    private $cache;

    /** @var string modulename prevents excessive calls to get_string */
    private static $modulename = null;

    /** @var string modulenameplural prevents excessive calls to get_string */
    private static $modulenameplural = null;

    /**
     * Constructor for the base qcreate class.
     *
     * @param mixed $coursemodulecontext context|null the course module context
     *                                   (or the course context if the coursemodule has not been
     *                                   created yet).
     * @param mixed $coursemodule the current course module if it was already loaded,
     *                            otherwise this class will load one from the context as required.
     * @param mixed $course the current course  if it was already loaded,
     *                      otherwise this class will load one from the context as required.
     */
    public function __construct($coursemodulecontext, $coursemodule, $course) {
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;

        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();
    }

    /**
     * Set the submitted form data.
     *
     * @param stdClass $data The form data (instance)
     */
    public function set_instance(stdClass $data) {
        $this->instance = $data;
    }

    /**
     * Set the context.
     *
     * @param context $context The new context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     * Display the activity, used by view.php
     *
     * The activity is displayed differently depending on your role,
     * the settings for the activity and the status of the activity.
     *
     * @param string $action The current action if any.
     * @return string - The page output.
     */
    public function view($action='') {
        $o = '';
        $mform = null;
        $notices = array();
        $nextpageparams = array();

        if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }

        if ($action == 'viewcourseindex') {
            $o .= $this->view_course_index();
        } else if ($action == 'overview') {
            $o .= $this->view_overview_page();
        } else if ($action == 'view') {
            $o .= $this->view_student_page();
        } else if ($action == 'confirmdelete') {
            $o .= $this->view_delete_confirm($mform);
        }
        return $o;
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;

        $adminconfig = $this->get_admin_config();
        $err = '';

        // Add the database record.
        $update = new stdClass();
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->timecreated = time();
        $update->course = $formdata->course;
        $update->courseid = $formdata->course;
        $update->grade = $formdata->grade;
        $update->graderatio = $formdata->graderatio;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->allowed = join(array_keys($formdata->allowed), ',');
        $update->totalrequired = $formdata->totalrequired;
        $update->studentqaccess = $formdata->studentqaccess;
        $update->timesync = 0;
        $update->timeopen = $formdata->timeopen;
        $update->timeclose = $formdata->timeclose;
        if (isset($formdata->completionquestions)) {
            $update->completionquestions = $formdata->completionquestions;
        } else {
            $update->completionquestions = 0;
        }
        $update->sendgradernotifications = $adminconfig->sendgradernotifications;
        if (isset($formdata->sendgradernotifications)) {
            $update->sendgradernotifications = $formdata->sendgradernotifications;
        }
        $update->sendstudentnotifications = $adminconfig->sendstudentnotifications;
        if (isset($formdata->sendstudentnotifications)) {
            $update->sendstudentnotifications = $formdata->sendstudentnotifications;
        }
        $returnid = $DB->insert_record('qcreate', $update);

        // Now save the requireds.
        $qtypemins = array_filter($formdata->qtype);
        if (count($qtypemins)) {
            foreach ($qtypemins as $key => $qtypemin) {
                $toinsert = new stdClass();
                $toinsert->no = $formdata->minimumquestions[$key];
                $toinsert->qtype = $qtypemin;
                $toinsert->qcreateid = $returnid;
                $DB->insert_record('qcreate_required', $toinsert);
            }
        }

        // We need to use context now, so we need to make sure all needed info is already in db.
        $DB->set_field('course_modules', 'instance', $returnid, array('id' => $formdata->coursemodule));

        $context = context_module::instance($formdata->coursemodule);
        $contexts = array($context);
        question_make_default_categories($contexts);

        $this->update_calendar($formdata->coursemodule);
        $this->update_gradebook(false, $formdata->coursemodule);

        $this->instance = $DB->get_record('qcreate', array('id' => $returnid), '*', MUST_EXIST);
        $requireds = $DB->get_records('qcreate_required',
                array('qcreateid' => $returnid), 'qtype', 'qtype, no, id');
        $this->instance->requiredqtypes = $requireds;

        // Cache the course record.
        $this->course = $DB->get_record('course', array('id' => $formdata->course), '*', MUST_EXIST);

        return $returnid;
    }

    /**
     * Delete all grades from the gradebook for this qcreate activity.
     *
     * @return bool
     */
    protected function delete_grades() {
        global $CFG;

        $result = grade_update('mod/qcreate',
                               $this->get_course()->id,
                               'mod',
                               'qcreate',
                               $this->get_instance()->id,
                               0,
                               null,
                               array('deleted' => 1));
        return $result == GRADE_UPDATE_OK;
    }

    /**
     * Delete this instance from the database.
     *
     * @return bool false if an error occurs
     */
    public function delete_instance() {
        global $DB;
        $result = true;

        // Delete files associated with this qcreate.
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }

        // Delete_records will throw an exception if it fails - so no need for error checking here.
        $DB->delete_records('qcreate_grades', array('qcreateid' => $this->get_instance()->id));
        $DB->delete_records('qcreate_required', array('qcreateid' => $this->get_instance()->id));

        // Delete items from the gradebook.
        if (! $this->delete_grades()) {
            $result = false;
        }

        $events = $DB->get_records('event', array('modulename' => 'qcreate', 'instance' => $this->get_instance()->id));
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }

        // Delete the instance.
        $DB->delete_records('qcreate', array('id' => $this->get_instance()->id));

        return $result;
    }

    public function get_required_qtypes() {
        return $this->get_instance()->requiredqtypes;
    }

    public function get_allowed_qtypes_list() {
        return $this->get_instance()->allowed;
    }

    public function get_allowed_qtypes_array() {
        return explode(',', $this->get_instance()->allowed);
    }

    public function get_allowed_qtypes_where() {
        global $DB;
        if ($this->get_instance()->allowed != 'ALL') {
            list($sql, $params) = $DB->get_in_or_equal($this->get_allowed_qtypes_array(), SQL_PARAMS_NAMED);
            $sql = 'q.qtype ' . $sql . ' AND ';
            return array($sql, $params);
        } else {
            return array('', array());
        }

    }

    /**
     * Actual implementation of the reset course functionality, delete all the
     * qcreate questions and local grades for course $data->courseid.
     *
     * @param stdClass $data the data submitted from the reset course.
     * @return array status array
     */
    public function reset_userdata($data) {
        global $CFG, $DB;

        $componentstr = get_string('modulenameplural', 'qcreate');
        $status = array();

        $fs = get_file_storage();
        if (!empty($data->reset_qcreate)) {
            $qcreatessql = 'SELECT a.id
                             FROM {qcreate} a
                           WHERE a.course=:course';
            $params = array('course' => $data->courseid);

            if ($categoriesmods = $DB->get_records('question_categories',
                    array('contextid' => $this->get_context()->id), 'parent', 'id, parent, name, contextid')) {
                foreach ($categoriesmods as $category) {

                    // Delete all questions.
                    if ($questions = $DB->get_records('question',
                            array('category' => $category->id), '', 'id,qtype')) {
                        foreach ($questions as $question) {
                            question_delete_question($question->id);
                        }
                        $DB->delete_records('question', array('category' => $category->id));
                    }
                }
            }

            // Delete local grades.
            $DB->delete_records_select('qcreate_grades', "qcreateid IN ($qcreatessql)", $params);

            $status[] = array('component' => $this->get_module_name_plural(),
                              'item' => get_string('gradesdeleted', 'qcreate'),
                              'error' => false);

            if (!empty($data->reset_gradebook_grades)) {
                // Remove all grades from gradebook.
                require_once($CFG->dirroot.'/mod/qcreate/lib.php');
                qcreate_reset_gradebook($data->courseid);
            }
        }
        // Updating dates - shift may be negative too.
        if ($data->timeshift) {
            shift_course_mod_dates('qcreate',
                                    array('timeopen', 'timeclose'),
                                    $data->timeshift,
                                    $data->courseid, $this->get_instance()->id);
            $status[] = array('component' => $componentstr,
                              'item' => get_string('datechanged'),
                              'error' => false);
        }

        return $status;
    }

    /**
     * Update the gradebook information for this qcreate activity.
     *
     * @param bool $reset If true, will reset all grades in the gradbook for this qcreate activity
     * @param int $coursemoduleid This is required because it might not exist in the database yet
     * @return bool
     */
    public function update_gradebook($reset, $coursemoduleid) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/qcreate/lib.php');
        $qcreate = clone $this->get_instance();
        $qcreate->cmidnumber = $coursemoduleid;

        $param = null;
        if ($reset) {
            $param = 'reset';
        }

        return qcreate_grade_item_update($qcreate, $param);
    }

    /**
     * Update the calendar entries for this qcreate activity.
     *
     * @uses QCREATE_MAX_EVENT_LENGTH
     * @param int $coursemoduleid - Required to pass this in because it might
     *                              not exist in the database yet.
     * @return bool
     */
    public function update_calendar($coursemoduleid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/calendar/lib.php');

        // Special case for add_instance as the coursemodule has not been set yet.
        $instance = $this->get_instance();

        // Load the old events relating to this qcreate.
        $conds = array('modulename' => 'qcreate',
                       'instance' => $instance->id);
        $oldevents = $DB->get_records('event', $conds);

        $cm = get_coursemodule_from_instance('qcreate', $instance->id);

        $event = new stdClass;
        $event->name = $instance->name;
        $event->description = format_module_intro('qcreate', $instance, $cm->id);
        $event->courseid    = $instance->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'qcreate';
        $event->instance    = $instance->id;
        $event->timestart   = $instance->timeopen;
        $event->timeduration = $instance->timeclose - $instance->timeopen;
        $event->visible     = instance_is_visible('qcreate', $instance);
        $event->eventtype   = 'open';

        if ($instance->timeclose and $instance->timeopen and $event->timeduration <= QCREATE_MAX_EVENT_LENGTH) {
            // Single event for the whole qcreate.
            if ($oldevent = array_shift($oldevents)) {
                $event->id = $oldevent->id;
            } else {
                unset($event->id);
            }
            // The method calendar_event::create will reuse a db record if the id field is set.
            calendar_event::create($event);
        } else {
            // Separate start and end events.
            $event->timeduration  = 0;
            if ($instance->timeopen) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->name = $instance->name.' ('.get_string('qcreateopens', 'qcreate').')';
                calendar_event::create($event);
            }
            if ($instance->timeclose) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->name      = $instance->name.' ('.get_string('qcreatecloses', 'qcreate').')';
                $event->timestart = $instance->timeclose;
                $event->eventtype = 'close';
                calendar_event::create($event);
            }
        }
    }


    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance($formdata) {
        global $DB;

        $adminconfig = $this->get_admin_config();

        $update = new stdClass();
        $update->id = $formdata->instance;
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->course = $formdata->course;
        $update->courseid = $formdata->course;
        $update->grade = $formdata->grade;
        $update->graderatio = $formdata->graderatio;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->allowed = join(array_keys($formdata->allowed), ',');
        $update->totalrequired = $formdata->totalrequired;
        $update->studentqaccess = $formdata->studentqaccess;
        $update->timeopen = $formdata->timeopen;
        $update->timeclose = $formdata->timeclose;
        if (isset($formdata->completionquestions)) {
            $update->completionquestions = $formdata->completionquestions;
        } else {
            $update->completionquestions = 0;
        }
        $update->sendgradernotifications = $adminconfig->sendgradernotifications;
        if (isset($formdata->sendgradernotifications)) {
            $update->sendgradernotifications = $formdata->sendgradernotifications;
        }
        $update->sendstudentnotifications = $adminconfig->sendstudentnotifications;
        if (isset($formdata->sendstudentnotifications)) {
            $update->sendstudentnotifications = $formdata->sendstudentnotifications;
        }
        $result = $DB->update_record('qcreate', $update);
        $this->instance = $DB->get_record('qcreate', array('id' => $update->id), '*', MUST_EXIST);

        $DB->delete_records('qcreate_required', array('qcreateid' => $update->id));
        // TODO re-use old records.
        $qtypemins = array_filter($formdata->qtype);
        if (count($qtypemins)) {
            foreach ($qtypemins as $key => $qtypemin) {
                $toinsert = new stdClass();
                $toinsert->no = $formdata->minimumquestions[$key];
                $toinsert->qtype = $qtypemin;
                $toinsert->qcreateid = $update->id;
                $DB->insert_record('qcreate_required', $toinsert);
            }
        }
        $requireds = $DB->get_records('qcreate_required',
                array('qcreateid' => $update->id), 'qtype', 'qtype, no, id');
        $this->instance->requiredqtypes = $requireds;
        $this->update_calendar($this->get_course_module()->id);
        $this->update_gradebook(false, $this->get_course_module()->id);
        qcreate_student_q_access_sync($this->get_context(), $this->get_instance(), true);
        return $result;
    }

    /**
     * Get the name of the current module.
     *
     * @return string the module name (Assignment)
     */
    protected function get_module_name() {
        if (isset(self::$modulename)) {
            return self::$modulename;
        }
        self::$modulename = get_string('modulename', 'qcreate');
        return self::$modulename;
    }

    /**
     * Get the plural name of the current module.
     *
     * @return string the module name plural (Question Creations)
     */
    public function get_module_name_plural() {
        if (isset(self::$modulenameplural)) {
            return self::$modulenameplural;
        }
        self::$modulenameplural = get_string('modulenameplural', 'qcreate');
        return self::$modulenameplural;
    }

    /**
     * Has this qcreate been constructed from an instance?
     *
     * @return bool
     */
    public function has_instance() {
        return $this->instance || $this->get_course_module();
    }

    /**
     * Get the settings for the current instance of this qcreate activity
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $qcreateid = $this->get_course_module()->instance;
            $params = array('id' => $qcreateid);
            $this->instance = $DB->get_record('qcreate', $params, '*', MUST_EXIST);
            $params = array('qcreateid' => $qcreateid);
            $requireds = $DB->get_records('qcreate_required',
                    $params, 'qtype', 'qtype, no, id');
            $this->instance->requiredqtypes = $requireds;
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the qcreate class. ' .
                                       'Cannot load the qcreate record.');
        }
        return $this->instance;
    }

    /**
     * Get the primary grade item for this qcreate instance.
     *
     * @return stdClass The grade_item record
     */
    public function get_grade_item() {
        if ($this->gradeitem) {
            return $this->gradeitem;
        }
        $instance = $this->get_instance();
        $params = array('itemtype' => 'mod',
                        'itemmodule' => 'qcreate',
                        'iteminstance' => $instance->id,
                        'courseid' => $instance->course,
                        'itemnumber' => 0);
        $this->gradeitem = grade_item::fetch($params);
        if (!$this->gradeitem) {
            throw new coding_exception('Improper use of the qcreate class. ' .
                                       'Cannot load the grade item.');
        }
        return $this->gradeitem;
    }

    /**
     * Get the question category
     *
     * @return object question_category
     */
    public function get_question_category() {
        return question_get_default_category($this->get_context()->id);
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the qcreate class. ' .
                                       'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }


    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('qcreate',
                                                           $this->context->instanceid,
                                                           0,
                                                           false,
                                                           MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Return a grade in user-friendly form, whether it's a scale or not.
     *
     * @param mixed $grade int|null
     * @param boolean $editing Are we allowing changes to this grade?
     * @param int $userid The user id the grade belongs to
     * @return string User-friendly representation of grade
     */
    public function display_grade($grade, $editing, $userid=0) {
        global $DB;

        static $scalegrades = array();

        $o = '';

        if ($this->get_instance()->grade >= 0) {
            // Normal number.
            if ($editing && $this->get_instance()->grade > 0) {
                if ($grade < 0) {
                    $displaygrade = '';
                } else {
                    $displaygrade = format_float($grade, 2);
                }
                $o .= '<label class="accesshide" for="quickgrade_' . $userid . '">' .
                       get_string('usergrade', 'qcreate') .
                       '</label>';
                $o .= '<input type="text"
                              id="quickgrade_' . $userid . '"
                              name="quickgrade_' . $userid . '"
                              value="' .  $displaygrade . '"
                              size="6"
                              maxlength="10"
                              class="quickgrade"/>';
                $o .= '&nbsp;/&nbsp;' . format_float($this->get_instance()->grade, 2);
                return $o;
            } else {
                if ($grade == -1 || $grade === null) {
                    $o .= '-';
                } else {
                    $item = $this->get_grade_item();
                    $o .= grade_format_gradevalue($grade, $item);
                    if ($item->get_displaytype() == GRADE_DISPLAY_TYPE_REAL) {
                        // If displaying the raw grade, also display the total value.
                        $o .= '&nbsp;/&nbsp;' . format_float($this->get_instance()->grade, 2);
                    }
                }
                return $o;
            }

        } else {
            // Scale.
            if (empty($this->cache['scale'])) {
                if ($scale = $DB->get_record('scale', array('id' => -($this->get_instance()->grade)))) {
                    $this->cache['scale'] = make_menu_from_list($scale->scale);
                } else {
                    $o .= '-';
                    return $o;
                }
            }
            if ($editing) {
                $o .= '<label class="accesshide"
                              for="quickgrade_' . $userid . '">' .
                      get_string('usergrade', 'qcreate') .
                      '</label>';
                $o .= '<select name="quickgrade_' . $userid . '" class="quickgrade">';
                $o .= '<option value="-1">' . get_string('nograde') . '</option>';
                foreach ($this->cache['scale'] as $optionid => $option) {
                    $selected = '';
                    if ($grade == $optionid) {
                        $selected = 'selected="selected"';
                    }
                    $o .= '<option value="' . $optionid . '" ' . $selected . '>' . $option . '</option>';
                }
                $o .= '</select>';
                return $o;
            } else {
                $scaleid = (int)$grade;
                if (isset($this->cache['scale'][$scaleid])) {
                    $o .= $this->cache['scale'][$scaleid];
                    return $o;
                }
                $o .= '-';
                return $o;
            }
        }
    }

    public function questions_of_type($questions) {
        $questionsofqtype = array();
        if ($questions) {
            foreach ($questions as $key => $question) {
                $questionsofqtype[$question->qtype][] = $question;
            }
        }
        return $questionsofqtype;
    }

    public static function qtype_menu() {
        $types = question_bank::get_creatable_qtypes();
        $returntypes = array();

        foreach ($types as $name => $qtype) {
            if ($name != 'randomsamatch') {
                $returntypes[$name] = $qtype->local_name();
            }
        }
        return $returntypes;
    }

    /**
     * Return a summary of the student activity for this qcreate
     * the created questions summary and the grading summary.
     *
     * @param stdClass $user the user to print the report for
     * @param bool $showlinks - Return plain text or links to the questions
     * @return string - the html summary
     */
    public function view_student_summary($user, $showlinks) {
        global $CFG, $DB, $PAGE, $OUTPUT;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        $instance = $this->get_instance();

        $o = '';

        $gradinginfo = grade_get_grades($this->get_course()->id,
                                    'mod',
                                    'qcreate',
                                    $instance->id,
                                    $user->id);

        $gradingitem = null;
        $gradebookgrade = null;
        if (isset($gradinginfo->items[0])) {
            $gradingitem = $gradinginfo->items[0];
            $gradebookgrade = $gradingitem->grades[$user->id];
        }

        // Only show the grade if it is not hidden in gradebook.
        if (!$gradebookgrade->hidden) {
            $gradefordisplay = $this->display_grade($gradebookgrade->grade, false);
            $gradeddate = $gradebookgrade->dategraded;
            if (isset($grade->grader)) {
                $grader = $DB->get_record('user', array('id' => $grade->grader));
            }
        }

        if (!empty($gradefordisplay)) {
            $o .= get_string('grade').': '.$gradefordisplay;
        }
        return $o;
    }

    /**
     * Load a count of created questions in the current module that require grading or regrading.
     * This means the question modification time is more recent than the
     * qcreate_grades timemarked time.
     *
     * @return int number of matching submissions
     */
    public function count_questionss_need_grading() {
        global $DB;

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = get_enrolled_sql($this->get_context(), 'mod/qcreate:submit', $currentgroup, true);

        $params['qcreateid'] = $this->get_instance()->id;

        $sql = 'TODO';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Load a count of local grades.
     *
     * @return int number of grades
     */
    public function count_grades() {
        global $DB;

        if (!$this->has_instance()) {
            return 0;
        }

        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        list($esql, $params) = get_enrolled_sql($this->get_context(), 'mod/qcreate:submit', $currentgroup, true);

        $params['qcreateid'] = $this->get_instance()->id;

        $sql = 'SELECT COUNT(g.userid)
                   FROM {qcreate_grades} g
                   JOIN(' . $esql . ') e ON e.id = g.userid
                   WHERE g.qcreateid = :qcreateid';

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Count the number of questions created by an user
     *
     * @ param int $userid id of the user, 0 means all users
     *
     * @return int number of questions
     */
    public function count_user_questions($userid=0) {
        global $DB;

        list($whereqtype, $params) = $this->get_allowed_qtypes_where();
        $whereuser = '';
        if ($userid) {
            $params['userid'] = $userid;
            $whereuser = 'q.createdby = :userid AND ';
        }

        $params['contextid'] = $this->get_context()->id;
        $countsql = 'SELECT COUNT(q.id) FROM {question} q,{question_categories} c '.
                   'WHERE ' . $whereqtype . $whereuser .
                    'q.hidden=\'0\' AND q.parent=\'0\' AND q.category = c.id and c.contextid = :contextid';
        return $DB->count_records_sql($countsql, $params);
    }

    /**
     * Update a local grade.
     *
     * @param stdClass $grade a grade record keyed on id
     * @return bool true for success
     */
    public function update_local_grade($grade) {
        global $DB;

        if ($grade->grade && $grade->grade != -1) {
            if ($this->get_instance()->grade > 0) {
                if (!is_numeric($grade->grade)) {
                    return false;
                } else if ($grade->grade > $this->get_instance()->grade) {
                    return false;
                } else if ($grade->grade < 0) {
                    return false;
                }
            } else {
                // This is a scale.
                if ($scale = $DB->get_record('scale', array('id' => -($this->get_instance()->grade)))) {
                    $scaleoptions = make_menu_from_list($scale->scale);
                    if (!array_key_exists((int) $grade->grade, $scaleoptions)) {
                        return false;
                    }
                }
            }
        }

        $result = $DB->update_record('qcreate_grades', $grade);
        return $result;
    }



    /**
     * View a summary listing of all qcreate activities in the current course.
     *
     * @return string
     */
    private function view_course_index() {
        global $USER;

        $o = '';

        $course = $this->get_course();

        if (!$cms = get_coursemodules_in_course('qcreate', $course->id)) {
            $o .= $this->get_renderer()->notification(get_string('thereareno', 'moodle', $this->get_module_name_plural()));
            $o .= $this->get_renderer()->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
            return $o;
        }

        $strsectionname = '';
        $usesections = course_format_uses_sections($course->format);
        $modinfo = get_fast_modinfo($course);

        if ($usesections) {
            $strsectionname = get_string('sectionname', 'format_'.$course->format);
            $sections = $modinfo->get_section_info_all();
        }
        $courseindexsummary = new qcreate_course_index_summary($usesections, $strsectionname);

        $timenow = time();

        $currentsection = '';
        foreach ($modinfo->instances['qcreate'] as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            $sectionname = '';
            if ($usesections && $cm->sectionnum) {
                $sectionname = get_section_name($course, $sections[$cm->sectionnum]);
            }

            $submitted = '';
            $context = context_module::instance($cm->id);

            $qcreateobj = new qcreate($context, $cm, $course);

            $timeopen = $qcreateobj->get_instance()->timeopen;
            $timeclose = $qcreateobj->get_instance()->timeclose;

            if (has_capability('mod/qcreate:grade', $context)) {
                $questionsnumber = $qcreateobj->count_user_questions();

            } else if (has_capability('mod/qcreate:submit', $context)) {
                $questionsnumber = $qcreateobj->count_user_questions($USER->id);
            }
            $gradinginfo = grade_get_grades($course->id, 'mod', 'qcreate', $cm->instance, $USER->id);
            if (isset($gradinginfo->items[0]->grades[$USER->id]) &&
                    !$gradinginfo->items[0]->grades[$USER->id]->hidden ) {
                $grade = $gradinginfo->items[0]->grades[$USER->id]->str_grade;
            } else {
                $grade = '-';
            }

            $courseindexsummary->add_qcreate_info($cm->id, $cm->name, $sectionname,
                    $timeopen, $timeclose, $questionsnumber, $grade);

        }

        $o .= $this->get_renderer()->render($courseindexsummary);
        $o .= $this->view_footer();

        return $o;
    }

    /**
     * View overview page.
     *
     * @return string
     */
    private function view_overview_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

        $o = '';
        $o .= $this->get_renderer()->render(new qcreate_header($instance,
                                                      $this->get_context(),
                                                      true,
                                                      $this->get_course_module()->id));

        $o .= $this->get_renderer()->render(new qcreate_teacher_overview($instance,
                                                      $this->get_context(),
                                                      $this->get_course_module()->id,
                                                      $this->is_activity_open()));

        $o .= $this->view_footer();

        $params = array(
            'courseid' => $this->get_course()->id,
            'context' => $this->get_context(),
            'other' => array(
                'qcreateid' => $this->get_instance()->id
            )
        );
        $event = \mod_qcreate\event\overview_viewed::create($params);
        $event->trigger();

        return $o;
    }

    /**
     * Student viewpage.
     *
     * @return string
     */
    private function view_student_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();
        $cat = $this->get_question_category()->id;
        $cm = $this->get_course_module();

        list($whereqtype, $params) = $this->get_allowed_qtypes_where();
        $params['userid'] = $USER->id;
        $whereuser = 'q.createdby = :userid AND ';
        $params['contextid'] = $this->get_context()->id;

        $questionsql = "SELECT q.*, c.id as cid, c.name as cname, g.grade, g.gradecomment, g.id as gid
                FROM {question_categories} c, {question} q
                LEFT JOIN {qcreate_grades} g ON q.id = g.questionid
                                                              AND g.qcreateid = {$instance->id}
                WHERE $whereqtype $whereuser c.contextid = :contextid AND c.id = q.category AND q.hidden='0' AND q.parent='0'";
        $questions = $DB->get_records_sql($questionsql, $params);

        // Calculate number of question done and number of questions still to do
        // For each required qtype.
        $qtyperequired = 0; // Total of required questions.
        $qtypedone = 0;
        $requiredquestions = array();
        $extraquestions = array();
        $qtypeqs = $this->questions_of_type($questions);
        if ($instance->requiredqtypes) {
            foreach ($instance->requiredqtypes as $qtype => $required) {
                $i = 1;
                $qtyperequired += $required->no;
                if (!empty($qtypeqs[$qtype])) {
                    $instance->requiredqtypes[$qtype]->done =
                            (count($qtypeqs[$qtype]) > $required->no) ? $required->no : count($qtypeqs[$qtype]);
                    $instance->requiredqtypes[$qtype]->stillrequiredno =
                            $instance->requiredqtypes[$qtype]->no - $instance->requiredqtypes[$qtype]->done;
                    while (($i <= $required->no) && ($qtypeq = array_shift($qtypeqs[$qtype]))) {
                        $requiredquestions[$qtype][] = $qtypeq;
                        $i++;
                    }
                } else {
                    $instance->requiredqtypes[$qtype]->done = 0;
                    $instance->requiredqtypes[$qtype]->stillrequiredno = $required->no;
                    $requiredquestions[$qtype] = array();
                }
                $qtypedone += $instance->requiredqtypes[$qtype]->done;
            }
        }

        // Calculate number of extra questions done for each allowed qtype.
        $extras = array();
        // If some extras questions are graded.
        $extraquestionsgraded = $qtyperequired < (int)$instance->totalrequired ? $instance->totalrequired - $qtyperequired : 0;
        if ($instance->allowed != 'ALL') {
            $qtypesallowed = explode(',', $instance->allowed);
        } else {
            $qtypesallowed = array_keys(self::qtype_menu());
        }

        $extraquestionsdone = 0;
        foreach ($qtypesallowed as $qtypeallowed) {
            $countqtypes = isset($qtypeqs[$qtypeallowed]) ? count($qtypeqs[$qtypeallowed]) : 0;
            $extraquestionsdone += $countqtypes;

            if ($countqtypes) {
                // There are extra questions for this qtype.
                $extras[$qtypeallowed] = $countqtypes;
                $extraquestions[$qtypeallowed] = $qtypeqs[$qtypeallowed];
            } else {
                $extraquestions[$qtypeallowed] = array();
            }
        }

        // Grade infos.
        $gradinginfo = grade_get_grades($this->get_course()->id, 'mod', 'qcreate', $this->get_instance()->id, $USER->id);
        $gradeforuser = $gradinginfo->items[0]->grades[$USER->id];
        $grademax = $gradinginfo->items[0]->grademax;
        $studentgrade = new stdClass();
        if (!empty($gradeforuser->dategraded)) {
            $fullgrade = new stdClass();
            $fullgrade->grade = (float)$gradeforuser->str_grade;
            $fullgrade->outof = (float)$gradinginfo->items[0]->grademax;
            $studentgrade->fullgrade = $fullgrade;
            if (!empty($this->get_instance()->graderatio)) {
                $automaticgrade = new stdClass();
                $automaticquestiongrade = $gradinginfo->items[0]->grademax * ($this->get_instance()->graderatio / 100) /
                       $this->get_instance()->totalrequired;
                $automaticgrade->outof = $gradinginfo->items[0]->grademax * ($this->get_instance()->graderatio / 100);
                $automaticgrade->done = ($extraquestionsdone + $qtypedone);
                $automaticgrade->required = $this->get_instance()->totalrequired;
                if ($automaticgrade->done < $automaticgrade->required) {
                    $automaticgrade->grade = $automaticgrade->done * $automaticquestiongrade;
                } else {
                    $automaticgrade->grade = $automaticgrade->outof;
                }
                $studentgrade->automaticgrade = $automaticgrade;
                if ($this->get_instance()->graderatio != 100) {
                    $manualgrade = new stdClass();
                    $manualgrade->grade = $gradeforuser->grade - $automaticgrade->grade;
                    $manualgrade->outof = $gradinginfo->items[0]->grademax - $automaticgrade->outof;
                    $studentgrade->manualgrade = $manualgrade;
                }
            }
        }
        $o = '';
        $o .= $this->get_renderer()->render(new qcreate_header($instance,
                                                      $this->get_context(),
                                                      true,
                                                      $this->get_course_module()->id));

        $o .= $this->get_renderer()->render(new qcreate_student_view($instance,
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
                                                      $this->get_context(),
                                                      $this->get_course_module()->id,
                                                      $this->is_activity_open()));

        $o .= $this->view_footer();

        // Write log entry.
        $params = array(
            'objectid' => $instance->id,
            'context' => $this->get_context()
        );
        $event = \mod_qcreate\event\course_module_viewed::create($params);
        $event->add_record_snapshot('qcreate', $instance);
        $event->trigger();

        return $o;
    }

    /**
     * Show a confirmation page to make sure they want to delete a created question.
     *
     * @return string
     */
    protected function view_delete_confirm() {

        $o = '';
        $header = new qcreate_header($this->get_instance(),
                                    $this->get_context(),
                                    false,
                                    $this->get_course_module()->id);
        $o .= $this->get_renderer()->render($header);
        $delete  = optional_param('delete', 0, PARAM_INT);  // Question id to delete.
        $urlparams = array('id' => $this->get_course_module()->id,
                           'action' => 'view',
                           'confirm' => 1,
                           'delete' => $delete,
                           'sesskey' => sesskey());
        $confirmurl = new moodle_url('/mod/qcreate/view.php', $urlparams);

        $urlparams = array('id' => $this->get_course_module()->id);
        $cancelurl = new moodle_url('/mod/qcreate/view.php', $urlparams);

        $o .= $this->get_renderer()->confirm(get_string('confirmdeletequestion', 'qcreate'),
                                             $confirmurl,
                                             $cancelurl);
        $o .= $this->view_footer();

        return $o;
    }
    /**
     * Display the page footer.
     *
     * @return string
     */
    protected function view_footer() {
        // When viewing the footer during PHPUNIT tests a set_state error is thrown.
        if (!PHPUNIT_TEST) {
            return $this->get_renderer()->render_footer();
        }

        return '';
    }

    /**
     * Does this user have grade permission for this qcreate activity?
     *
     * @return bool
     */
    public function can_grade() {
        // Permissions check.
        if (!has_capability('mod/qcreate:grade', $this->context)) {
            return false;
        }

        return true;
    }

    /**
     * Load and cache the admin config for this module.
     *
     * @return stdClass the plugin config
     */
    public function get_admin_config() {
        if ($this->adminconfig) {
            return $this->adminconfig;
        }
        $this->adminconfig = get_config('qcreate');
        return $this->adminconfig;
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return qcreate_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_qcreate');
        return $this->output;
    }

    /**
     * This will retrieve a grade object from the db, optionally creating it if required.
     *
     * @param int $userid The user we are grading
     * @param bool $create If true the grade will be created if it does not exist
     * @return stdClass The grade record
     */
    public function get_local_grade($userid, $create, $questionid) {
        global $DB, $USER;

        // If the userid is not null then use userid.
        if (!$userid) {
            $userid = $USER->id;
        }

        $params = array('qcreateid' => $this->get_instance()->id, 'userid' => $userid, 'questionid' => $questionid);
        $grades = $DB->get_records('qcreate_grades', $params, 'timemarked DESC', '*', 0, 1);

        if ($grades) {
            return reset($grades);
        }
        if ($create) {
            $grade = new stdClass();
            $grade->qcreateid   = $this->get_instance()->id;
            $grade->userid       = $userid;
            $grade->timemarked = time();
            $grade->grade = -1;
            $grade->teacher = $USER->id;

            $gid = $DB->insert_record('qcreate_grades', $grade);
            $grade->id = $gid;
            return $grade;
        }
        return false;
    }

    /**
     * This will retrieve a grade object from the db.
     *
     * @param int $gradeid The id of the grade
     * @return stdClass The grade record
     */
    protected function get_grade($gradeid) {
        global $DB;

        $params = array('qcreateid' => $this->get_instance()->id, 'id' => $gradeid);
        return $DB->get_record('qcreate_grades', $params, '*', MUST_EXIST);
    }

    /**
     * See if this qcreate activity has a grade yet.
     *
     * @param int $userid
     * @return bool
     */
    protected function is_graded($userid) {
        $grade = $this->get_user_grade($userid, false);
        if ($grade) {
            return ($grade->grade !== null && $grade->grade >= 0);
        }
        return false;
    }

    /**
     * Is this qcreate activity open?
     * @return bool
     */
    public function is_activity_open($timenow = null) {

        if (!$timenow) {
            $timenow = time();
        }
        $qcreate = $this->get_instance();
        return ($qcreate->timeopen == 0 ||($qcreate->timeopen < $timenow)) &&
        ($qcreate->timeclose == 0 ||($qcreate->timeclose > $timenow));
    }

    /**
     * Returns a list of teachers that should be grading given userid created questions.
     *
     * @param int $userid The userid to grade
     * @return array
     */
    protected function get_graders($userid) {
        // Potential graders should be active users only.
        $potentialgraders = get_enrolled_users($this->context, "mod/qcreate:grade", null, 'u.*', null, null, null, true);

        $graders = array();
        if (groups_get_activity_groupmode($this->get_course_module()) == SEPARATEGROUPS) {
            if ($groups = groups_get_all_groups($this->get_course()->id, $userid, $this->get_course_module()->groupingid)) {
                foreach ($groups as $group) {
                    foreach ($potentialgraders as $grader) {
                        if ($grader->id == $userid) {
                            // Do not send self.
                            continue;
                        }
                        if (groups_is_member($group->id, $grader->id)) {
                            $graders[$grader->id] = $grader;
                        }
                    }
                }
            } else {
                // User not in group, try to find graders without group.
                foreach ($potentialgraders as $grader) {
                    if ($grader->id == $userid) {
                        // Do not send self.
                        continue;
                    }
                    if (!groups_has_membership($this->get_course_module(), $grader->id)) {
                        $graders[$grader->id] = $grader;
                    }
                }
            }
        } else {
            foreach ($potentialgraders as $grader) {
                if ($grader->id == $userid) {
                    // Do not send self.
                    continue;
                }
                // Must be enrolled.
                if (is_enrolled($this->get_course_context(), $grader->id)) {
                    $graders[$grader->id] = $grader;
                }
            }
        }
        return $graders;
    }

    /**
     * Format a notification for plain text.
     *
     * @param string $messagetype
     * @param stdClass $info
     * @param stdClass $course
     * @param stdClass $context
     * @param string $modulename
     * @param string $qcreatename
     */
    protected static function format_notification_message_text($messagetype,
                                                             $info,
                                                             $course,
                                                             $context,
                                                             $modulename,
                                                             $qcreatename) {
        $formatparams = array('context' => $context->get_course_context());
        $posttext  = format_string($course->shortname, true, $formatparams) .
                     ' -> ' .
                     $modulename .
                     ' -> ' .
                     format_string($qcreatename, true, $formatparams) . "\n";
        $posttext .= '---------------------------------------------------------------------' . "\n";
        $posttext .= get_string($messagetype . 'text', 'qcreate', $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
        return $posttext;
    }

    /**
     * Format a notification for HTML.
     *
     * @param string $messagetype
     * @param stdClass $info
     * @param stdClass $course
     * @param stdClass $context
     * @param string $modulename
     * @param stdClass $coursemodule
     * @param string $qcreatename
     */
    protected static function format_notification_message_html($messagetype,
                                                             $info,
                                                             $course,
                                                             $context,
                                                             $modulename,
                                                             $coursemodule,
                                                             $qcreatename) {
        global $CFG;
        $formatparams = array('context' => $context->get_course_context());
        $posthtml  = '<p><font face="sans-serif">' .
                     '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' .
                     format_string($course->shortname, true, $formatparams) .
                     '</a> ->' .
                     '<a href="' . $CFG->wwwroot . '/mod/qcreate/index.php?id=' . $course->id . '">' .
                     $modulename .
                     '</a> ->' .
                     '<a href="' . $CFG->wwwroot . '/mod/qcreate/view.php?id=' . $coursemodule->id . '">' .
                     format_string($qcreatename, true, $formatparams) .
                     '</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>' . get_string($messagetype . 'html', 'qcreate', $info) . '</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }

    /**
     * Message someone about something (static so it can be called from cron).
     *
     * @param stdClass $userfrom
     * @param stdClass $userto
     * @param string $messagetype
     * @param string $eventtype
     * @param int $updatetime
     * @param stdClass $coursemodule
     * @param stdClass $context
     * @param stdClass $course
     * @param string $modulename
     * @param string $qcreatename
     * @param bool $blindmarking
     * @param int $uniqueidforuser
     * @return void
     */
    public static function send_qcreate_notification($userfrom,
                                                        $userto,
                                                        $messagetype,
                                                        $eventtype,
                                                        $questionname,
                                                        $updatetime,
                                                        $coursemodule,
                                                        $context,
                                                        $course,
                                                        $modulename,
                                                        $qcreatename) {
        global $CFG;

        $info = new stdClass();
        $info->username = fullname($userfrom, true);
        $info->questionname = $questionname;
        $info->qcreate = format_string($qcreatename, true, array('context' => $context));
        $info->url = $CFG->wwwroot.'/mod/qcreate/view.php?id='.$coursemodule->id;
        $info->timeupdated = userdate($updatetime, get_string('strftimerecentfull'));

        $postsubject = get_string($messagetype . 'small', 'qcreate', $info);
        $posttext = self::format_notification_message_text($messagetype,
                                                           $info,
                                                           $course,
                                                           $context,
                                                           $modulename,
                                                           $qcreatename);
        $posthtml = '';
        if ($userto->mailformat == 1) {
            $posthtml = self::format_notification_message_html($messagetype,
                                                               $info,
                                                               $course,
                                                               $context,
                                                               $modulename,
                                                               $coursemodule,
                                                               $qcreatename);
        }

        $eventdata = new stdClass();
        $eventdata->modulename       = 'qcreate';
        $eventdata->userfrom         = $userfrom;
        $eventdata->userto           = $userto;
        $eventdata->subject          = $postsubject;
        $eventdata->fullmessage      = $posttext;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml  = $posthtml;
        $eventdata->smallmessage     = $postsubject;

        $eventdata->name            = $eventtype;
        $eventdata->component       = 'mod_qcreate';
        $eventdata->notification    = 1;
        $eventdata->contexturl      = $info->url;
        $eventdata->contexturlname  = $info->qcreate;

        message_send($eventdata);
    }

    /**
     * Message someone about something.
     *
     * @param stdClass $userfrom
     * @param stdClass $userto
     * @param string $messagetype
     * @param string $eventtype
     * @param int $updatetime
     * @return void
     */
    public function send_notification($userfrom,
                                      $userto,
                                      $messagetype,
                                      $eventtype,
                                      $questionname,
                                      $updatetime) {
        self::send_qcreate_notification($userfrom,
                                        $userto,
                                        $messagetype,
                                        $eventtype,
                                        $questionname,
                                        $updatetime,
                                        $this->get_course_module(),
                                        $this->get_context(),
                                        $this->get_course(),
                                        $this->get_module_name(),
                                        $this->get_instance()->name);
    }


    /**
     * Notify student that one of his questions was graded.
     *
     * @param stdClass $question
     * @return void
     */
    public function notify_student_question_graded(stdClass $question) {
        global $DB, $USER;

        if (!$this->get_instance()->sendstudentnotifications) {
            // No need to do anything.
            return;
        }

        $user = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
        $this->send_notification($USER,
                                 $user,
                                 'gradeavailable',
                                 'studentnotification',
                                 $question->name,
                                 $question->timemodified);
    }

    /**
     * Send notifications to graders upon student submissions.
     *
     * @param stdClass $question
     * @return void
     */
    public function notify_graders(stdClass $question) {
        global $DB, $USER;

        $instance = $this->get_instance();

        if (!$instance->sendgradernotifications) {
            // No need to do anything.
            return;
        }

        if ($question->createdby) {
            $user = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
        } else {
            $user = $USER;
        }
        $graders = $this->get_graders($user->id);
        foreach ($graders as $grader) {
            $this->send_notification($user,
                                     $grader,
                                     'questiontograde',
                                     'gradernotification',
                                     $question->name,
                                     $question->timemodified);
        }

    }

    /**
     * Save outcomes submitted from grading form.
     *
     * @param int $userid
     * @param stdClass $formdata
     * @param int $sourceuserid The user ID under which the outcome data is accessible. This is relevant
     *                          for an outcome set to a user but applied to an entire group.
     */
    protected function process_outcomes($userid, $formdata, $sourceuserid = null) {
        global $CFG, $USER;

        if (empty($CFG->enableoutcomes)) {
            return;
        }

        require_once($CFG->libdir.'/gradelib.php');

        $data = array();
        $gradinginfo = grade_get_grades($this->get_course()->id,
                                        'mod',
                                        'qcreate',
                                        $this->get_instance()->id,
                                        $userid);

        if (!empty($gradinginfo->outcomes)) {
            foreach ($gradinginfo->outcomes as $index => $oldoutcome) {
                $name = 'outcome_'.$index;
                $sourceuserid = $sourceuserid !== null ? $sourceuserid : $userid;
                if (isset($formdata->{$name}[$sourceuserid]) &&
                        $oldoutcome->grades[$userid]->grade != $formdata->{$name}[$sourceuserid]) {
                    $data[$index] = $formdata->{$name}[$sourceuserid];
                }
            }
        }
        if (count($data) > 0) {
            grade_update_outcomes('mod/qcreate',
                                  $this->course->id,
                                  'mod',
                                  'qcreate',
                                  $this->get_instance()->id,
                                  $userid,
                                  $data);
        }
    }
}

/**
 * @param int $cmid the course_module object for this qcreate.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @return string html for a number of icons linked to action pages for a
 * question - preview, edit / view and delete icons depending on user capabilities.
 */
function qcreate_question_action_icons($cmid, $question, $returnurl) {

    $html = qcreate_question_preview_button($question);
    $html .= qcreate_question_edit_button($cmid, $question, $returnurl);
    $html .= qcreate_question_delete_button($cmid, $question, $returnurl);
    return $html;
}

/**
 * @param int $cmid the course_module.id for this qcreate.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @return the HTML for an edit icon, view icon, or nothing for a question
 *      (depending on permissions).
 */
function qcreate_question_edit_button($cmid, $question, $returnurl) {
    global $CFG, $OUTPUT;

    // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
    static $stredit = null;
    static $strview = null;

    if ($stredit === null) {
        $stredit = get_string('edit');
        $strview = get_string('view');
    }

    // What sort of icon should we show?
    $action = '';
    if (!empty($question->id) &&
            (question_has_capability_on($question, 'edit', $question->category) ||
                    question_has_capability_on($question, 'move', $question->category))) {
        $action = $stredit;
        $icon = '/t/edit';
    } else if (!empty($question->id) &&
            question_has_capability_on($question, 'view', $question->category)) {
        $action = $strview;
        $icon = '/i/info';
    }

    // Build the icon.
    if ($action) {
        if ($returnurl instanceof moodle_url) {
            $returnurl = $returnurl->out_as_local_url(false);
        }
        $questionparams = array('returnurl' => $returnurl, 'cmid' => $cmid, 'id' => $question->id);
        $questionurl = new moodle_url("$CFG->wwwroot/question/question.php", $questionparams);
        return '<a title="' . $action . '" href="' . $questionurl->out() . '" class="iconsmall"><img src="' .
                $OUTPUT->pix_url($icon) . '" alt="' . $action . '" /></a>';
    } else {
        return '';
    }
}

/**
 * @param int $cmid the course_module.id for this qcreate.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @return the HTML for a delete icon, or nothing for a question
 *      (depending on permissions).
 */
function qcreate_question_delete_button($cmid, $question, $returnurl) {
    global $CFG, $OUTPUT;

    // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
    static $strdelete = null;
    if ($strdelete === null) {
        $strdelete = get_string('delete');
    }

    if (!empty($question->id) &&
            (question_has_capability_on($question, 'edit', $question->category))) {

        // Build the icon.
        $action = $strdelete;
        $icon = 't/delete';

        return '<a title="' . $action . '" href="' . $returnurl . '&amp;delete=' . $question->id. '" class="iconsmall"><img src="' .
                $OUTPUT->pix_url($icon) . '" alt="' . $action . '" /></a>';
    } else {
        return '';
    }
}

/**
 * @param object $qcreate the qcreate settings
 * @param object $question the question
 * @return the HTML for a preview question icon.
 */
function qcreate_question_preview_button($question) {
    global $CFG, $OUTPUT;
    // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
    static $strpreview = null;
    if ($strpreview === null) {
        $strpreview = get_string('preview', 'qcreate');
    }

    if (!question_has_capability_on($question, 'use', $question->category)) {
        return '';
    }

    $url = question_preview_url($question->id);

    // Build the icon.
    $image = $OUTPUT->pix_icon('t/preview', $strpreview);

    $action = new popup_action('click', $url, 'questionpreview',
            question_preview_popup_params());

    return $OUTPUT->action_link($url, $image, $action, array('title' => $strpreview, 'class' => 'iconsmall'));
}
