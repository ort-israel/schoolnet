<?php
// This file is part of Exabis Eportfolio
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Eportfolio is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require_once $CFG->libdir . '/formslib.php';
//require_once $CFG->libdir . '/filelib.php';

class block_exaport_comment_edit_form extends moodleform {

	function definition() {
		global $CFG, $USER, $DB;
		$mform = & $this->_form;

		$this->_form->_attributes['action'] = $_SERVER['REQUEST_URI'];

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_ACTION);
		$mform->setDefault('action', 'add');

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$mform->addElement('hidden', 'itemid');
		$mform->setType('itemid', PARAM_INT);
		$mform->setDefault('itemid', 0);

		$mform->addElement('hidden', 'userid');
		$mform->setType('userid', PARAM_INT);
		$mform->setDefault('userid', 0);
		
		$mform->addElement('header', 'comment', get_string("addcomment", "block_exaport"));

		$mform->addElement('editor', 'entry', get_string("comment", "block_exaport"),null, array('rows' => 10, 'maxbytes' => $CFG->block_exaport_max_uploadfile_size));
		$mform->setType('entry', PARAM_TEXT);
		$mform->addRule('entry', get_string("commentshouldnotbeempty", "block_exaport"), 'required', null, 'client');
		//$mform->setHelpButton('entry', array('writing', 'richtext'), false, 'editorhelpbutton');

		$mform->addElement('filemanager', 'file', get_string('file', 'block_exaport'), null, array('subdirs' => 0, 'maxfiles' => 1));

		/*
		fjungwirth: hide grading at this stage (meeting LS 4.7.16)
		
		if ($this->_customdata['gradingpermission']) {
			$mform->addElement('header', 'itemgrading', get_string("itemgrading", "block_exaport"));
			$itemgrade = $this->_customdata['itemgrade'];
			$mform->addElement('select', 'itemgrade', get_string('gradeitem', 'block_exaport'), range(0, 100));
			$mform->setDefault('itemgrade', $itemgrade);
			
			$slider = '<div id="slider"></div>';
			$mform->addElement('html',$slider);
		}
		*/
		
		$this->add_action_buttons(false, get_string('add'));
		
	}

}

class block_exaport_item_edit_form extends moodleform {

	function definition() {
		global $CFG, $USER, $DB;

		$type = $this->_customdata['type'];

		$mform = & $this->_form;
		
		$mform->addElement('header', 'general', get_string($type, "block_exaport"));

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', 0);

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_ACTION);
		$mform->setDefault('action', '');

		$mform->addElement('hidden', 'compids');
		$mform->setType('compids', PARAM_TEXT);
		$mform->setDefault('compids','');

		$mform->addElement('text', 'name', get_string("title", "block_exaport"), 'maxlength="255" size="60"');
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', get_string("titlenotemtpy", "block_exaport"), 'required', null, 'client');

		$mform->addElement('select', 'categoryid', get_string("category", "block_exaport"), array());
		$mform->addRule('categoryid', get_string("categorynotempty", "block_exaport"), 'required', null, 'client');
		$mform->setDefault('categoryid', 0);
		$this->category_select_setup();


		if ($type == 'link') {
			$mform->addElement('text', 'url', get_string("url", "block_exaport"), 'maxlength="255" size="60" value="http://"');
			$mform->setType('url', PARAM_TEXT);
			$mform->addRule('url', get_string("urlnotempty", "block_exaport"), 'required', null, 'client');
		}else{
			$mform->addElement('text', 'url', get_string("url", "block_exaport"), 'maxlength="255" size="60"');
			$mform->setType('url', PARAM_TEXT);
		}


		if ($type == 'link') {

		} elseif ($type == 'file') {
			if ($this->_customdata['action'] == 'assignment_import') {
				// assignment import.
				$mform->addElement('hidden', 'submissionid');
				$mform->setType('submissionid', PARAM_INT);
				$mform->addElement('hidden', 'fileid');
				$mform->setType('fileid', PARAM_TEXT);
			} elseif ($this->_customdata['action'] == 'add') {
				$mform->addElement('filemanager', 'file', get_string('file', 'block_exaport'), null, array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => $CFG->block_exaport_max_uploadfile_size));
				$mform->addRule('file', null, 'required', null, 'client');

			} else {
				// Filemanager for edit file.
				$mform->addElement('filemanager', 'file', get_string('file', 'block_exaport'), null, array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => $CFG->block_exaport_max_uploadfile_size));
				$mform->addRule('file', null, 'required', null, 'client');
			}
		}

		if (block_exaport_course_has_desp()) {
			$langcode=get_string("langcode","block_desp");
			 
			$sql = "SELECT lang.id,lang.".$langcode." as name FROM {block_desp_lang} lang WHERE id IN(SELECT langid FROM {block_desp_check_lang} WHERE userid=?) OR id IN (SELECT langid FROM {block_desp_lanhistories} WHERE userid=?) ORDER BY lang.".$langcode;
			$languages = $DB->get_records_sql_menu($sql, array($USER->id, $USER->id));
			 
			$languages[0]='';

			asort($languages);
			$mform->addElement('select', 'langid', get_string("desp_language", "block_exaport"), $languages);
			$mform->setType('langid', PARAM_INT);
		}

		if (isset($this->_customdata['useTextarea']) && $this->_customdata['useTextarea']) {
			// it has iframe, show textfield, no editor
			$mform->addElement('textarea', 'intro', get_string('intro', 'block_exaport'), 'rows="20" cols="50" style="width: 95%"');
			$mform->setType('intro', PARAM_RAW);
			if ($type == 'note')
				$mform->addRule('intro', get_string("intronotempty", "block_exaport"), 'required', null, 'client');
		} else {
			if (!isset($this->_customdata['textfieldoptions'])) {
				$this->_customdata['textfieldoptions'] = array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>99, 'context'=>context_user::instance($USER->id));
			}
			$mform->addElement('editor', 'intro_editor', get_string('intro', 'block_exaport'), null, $this->_customdata['textfieldoptions']);
			$mform->setType('intro_editor', PARAM_RAW);
			if ($type == 'note')
				$mform->addRule('intro_editor', get_string("intronotempty", "block_exaport"), 'required', null, 'client');
		}
		
		$mform->addElement('filemanager', 'iconfile', get_string('iconfile', 'block_exaport'), null, array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => $CFG->block_exaport_max_uploadfile_size, 'accepted_types' => array('image', 'web_image')));

		// Tags.
		if (!empty($CFG->usetags)) { 		
			// include_once($CFG->dirroot.'/tag/lib.php');
            // $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            
			$mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'block_exaportitem', 'component' => 'block_exaport'));
		}
		
		// $mform->addRule('iconfile', null, 'required', null, 'client');
		if(!empty($this->_customdata['allowedit']) || empty($this->_customdata['current'])) {
			$this->add_action_buttons($cancel = true, $submitlabel= get_string('submititem','block_exaport'));
		} else {
			$exampleid = $DB->get_field('block_exacompitemexample', 'exampleid', array('itemid' => $this->_customdata['current']->id));
			$url = new moodle_url("/blocks/exacomp/example_submission.php",array("courseid"=>$this->_customdata['course']->id,"newsubmission"=>true,"exampleid"=>$exampleid));
			
			$mform->addElement('hidden', 'allowedit');
			$mform->setType('allowedit', PARAM_INT);
			$mform->setDefault('allowedit', 0);
		
			$mform->disabledIf('name', 'allowedit', 'neq', 1);
			$mform->disabledIf('categoryid', 'allowedit', 'neq', 1);
			$mform->disabledIf('url', 'allowedit', 'neq', 1);
			$mform->disabledIf('file', 'allowedit', 'neq', 1);
			$mform->disabledIf('intro', 'allowedit', 'neq', 1);
			$mform->disabledIf('intro_editor', 'allowedit', 'neq', 1);
			$mform->disabledIf('iconfile', 'allowedit', 'neq', 1);
			
			if(!empty($this->_customdata['allowresubmission']))
				$mform->addElement('button', 'newsubmission', get_string("newsubmission","block_exacomp"),array('onclick'=>'location.href = " ' . str_replace("&amp;", "&", $url) . '"'));
			else 
				$mform->addElement('html',get_string("isgraded","block_exacomp"));
		}
	}

	function category_select_setup() {
		global $CFG, $USER, $DB;
		$mform = & $this->_form;
		$categorysselect = & $mform->getElement('categoryid');
		$categorysselect->removeOptions();

		$conditions = array("userid" => $USER->id, "pid" => 0);
		$outercategories = $DB->get_records_select("block_exaportcate", "userid = ? AND pid = ?", $conditions, "name asc");
		$categories = array(
				0 => block_exaport_get_root_category()->name
		);
		if ($outercategories) {
			$categories = $categories + rek_category_select_setup($outercategories, " ", $categories);
		}
		$categorysselect->loadArray($categories);
	}

}

function rek_category_select_setup($outercategories, $entryname, $categories){
	global $DB, $USER;
	foreach ($outercategories as $curcategory) {
		$categories[$curcategory->id] = $entryname.format_string($curcategory->name);
		$name = $entryname.format_string($curcategory->name);

		$conditions = array("userid" => $USER->id, "pid" => $curcategory->id);
		$inner_categories = $DB->get_records_select("block_exaportcate", "userid = ? AND pid = ?", $conditions, "name asc");
		if ($inner_categories) {
			$categories = rek_category_select_setup($inner_categories, $name.' &rarr; ', $categories);
		}
	}
	return $categories;
}
