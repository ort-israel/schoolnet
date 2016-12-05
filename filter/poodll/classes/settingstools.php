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

namespace filter_poodll;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');


/**
 *
 * This is a class containing static functions for general PoodLL filter things
 * like embedding recorders and managing them
 *
 * @package   filter_poodll
 * @since      Moodle 3.1
 * @copyright  2016 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class settingstools
{
	
public static function fetch_general_items(){
	$items = array();

        $items[] = new \admin_setting_heading('filter_poodll_registration_settings', get_string('filter_poodll_registration_heading', 'filter_poodll'), get_string('filter_poodll_registration_explanation', 'filter_poodll'));
	$items[] = new \admin_setting_configtextarea('filter_poodll_registrationkey', get_string('registrationkey', 'filter_poodll'), get_string('registrationkey_explanation', 'filter_poodll'), '');
	/*
        $items[] = new \admin_setting_configtext('filter_poodll_uploadkey', get_string('uploadkey', 'filter_poodll'), get_string('uploadkey_desc', 'filter_poodll'), '');
	$items[] = new \admin_setting_configtext('filter_poodll_uploadsecret', get_string('uploadsecret', 'filter_poodll'), get_string('uploadsecret_desc', 'filter_poodll'), '');
        */
	
        
	
        $items[] = new \admin_setting_configcheckbox('filter_poodll_cloudrecording', get_string('usecloudrecording', 'filter_poodll'), get_string('usecloudrecording_desc', 'filter_poodll'), 1);

	$options = array('2.x' => 'Version 2.x', '3.x'=>"Version 3.x");
	$items[] = new \admin_setting_configselect('filter_poodll_aws_sdk', get_string('awssdkversion', 'filter_poodll'), 
		get_string('awssdkversion_desc', 'filter_poodll'), '2.x', $options);

		//PoodLL Network Settings.
	$items[] = new \admin_setting_heading('filter_poodll_network_settings', get_string('filter_poodll_network_heading', 'filter_poodll'), '');
	$items[] = new \admin_setting_configtext('filter_poodll_servername', get_string('servername', 'filter_poodll'), '', 'tokyo.poodll.com');
	$items[] = new \admin_setting_configtext('filter_poodll_serverid', get_string('serverid', 'filter_poodll'), '', 'poodll');
	$items[] = new \admin_setting_configtext('filter_poodll_serverport', get_string('serverport', 'filter_poodll'), '', '1935', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_serverhttpport', get_string('serverhttpport', 'filter_poodll'), '', '443', PARAM_INT);
	$items[] = new \admin_setting_configcheckbox('filter_poodll_autotryports', get_string('autotryports', 'filter_poodll'), '', 1);


	
	//PoodLL player type settings.
        $items[] = new \admin_setting_configtext('filter_poodll_recorderorder', get_string('recorderorder', 'filter_poodll'), 
                    get_string('recorderorder_desc', 'filter_poodll'), 'mobile,media,flashaudio,red5,upload',PARAM_TEXT);

        
	$items[] = new \admin_setting_configcheckbox('filter_poodll_download_media_ok', get_string('showdownloadicon', 'filter_poodll'), '', 0);

	// PoodLL Flashcards
	$items[] = new \admin_setting_heading('filter_poodll_flashcards_settings', get_string('filter_poodll_flashcards_heading', 'filter_poodll'), '');
	$options = array('poodll' => 'PoodLL', 'owl'=>"Owl");
	$items[] = new \admin_setting_configselect('filter_poodll_flashcards_type', get_string('flashcardstype', 'filter_poodll'), '', 'poodll', $options);



	//audio capture settings
	$items[] = new \admin_setting_heading('filter_poodll_mic_settings', get_string('filter_poodll_mic_heading', 'filter_poodll'), '');
	$items[] = new \admin_setting_configtext('filter_poodll_studentmic', get_string('studentmic', 'filter_poodll'), '', '');
	$items[] = new \admin_setting_configtext('filter_poodll_micrate', get_string('micrate', 'filter_poodll'), '','22', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_micsilencelevel', get_string('micsilencelevel', 'filter_poodll'), '', '1', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_micgain', get_string('micgain', 'filter_poodll'), '', '50', PARAM_INT); 
	$items[] = new \admin_setting_configtext('filter_poodll_micecho', get_string('micecho', 'filter_poodll'), '', 'yes');
	$items[] = new \admin_setting_configtext('filter_poodll_micloopback', get_string('micloopback', 'filter_poodll'), '', 'no');
	$items[] = new \admin_setting_configcheckbox('filter_poodll_miccanpause', get_string('miccanpause', 'filter_poodll'), '', 0);
	$items[] = new \admin_setting_configtext('filter_poodll_mp3skin', get_string('mp3skin', 'filter_poodll'), 
			get_string('mp3skin_details', 'filter_poodll'), 'none');


	//video capture settings.
	$items[] = new \admin_setting_heading('filter_poodll_camera_settings', get_string('filter_poodll_camera_heading', 'filter_poodll'), '');
	$items[] = new \admin_setting_configtext('filter_poodll_studentcam', get_string('studentcam', 'filter_poodll'), '', '');
	$options = array('160' => '160x120', '320' => '320x240','480' => '480x360','640' => '640x480','800'=>'800x600','1024'=>'1024x768','1280'=>'1280x1024','1600' => '1600x1200',);
	$items[] = new \admin_setting_configselect('filter_poodll_capturewidth', get_string('capturewidth', 'filter_poodll'), '', '480', $options);
	//$items[] = new \admin_setting_configtext('filter_poodll_captureheight', get_string('captureheight', 'filter_poodll'), '', '240', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_capturefps', get_string('capturefps', 'filter_poodll'), '', '17', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_bandwidth', get_string('bandwidth', 'filter_poodll'), '', '0', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_picqual', get_string('picqual', 'filter_poodll'), '', '7', PARAM_INT);

	//mp3 recorder settings.
	$items[] = new \admin_setting_heading('filter_poodll_mp3recorder_settings', get_string('filter_poodll_mp3recorder_heading', 'filter_poodll'), '');
	$options = array('normal' => get_string('normal', 'filter_poodll'), 'tiny' => get_string('tiny', 'filter_poodll'));
	$items[] = new \admin_setting_configselect('filter_poodll_mp3recorder_size', get_string('size', 'filter_poodll'), '', 'normal', $options);



	/*
	//File Conversions
	*/
	$items[] = new \admin_setting_heading('filter_poodll_transcode_settings', get_string('transcode_heading', 'filter_poodll'), '');
	$items[] = new \admin_setting_configcheckbox('filter_poodll_videotranscode', get_string('videotranscode', 'filter_poodll'), get_string('videotranscodedetails', 'filter_poodll'), 0);
	$items[] = new \admin_setting_configcheckbox('filter_poodll_audiotranscode', get_string('audiotranscode', 'filter_poodll'), get_string('audiotranscodedetails', 'filter_poodll'), 0);
	$items[] = new \admin_setting_configcheckbox('filter_poodll_ffmpeg', get_string('ffmpeg', 'filter_poodll'), get_string('ffmpeg_details', 'filter_poodll'), 0);
	$items[] = new \admin_setting_configtext('filter_poodll_ffmpeg_mp3opts', get_string('mp3opts', 'filter_poodll'), get_string('mp3opts_details', 'filter_poodll'), '');
	$items[] = new \admin_setting_configtext('filter_poodll_ffmpeg_mp4opts', get_string('mp4opts', 'filter_poodll'), get_string('mp4opts_details', 'filter_poodll'), '');
	$items[] = new \admin_setting_configcheckbox('filter_poodll_bgtranscode_video', get_string('bgtranscode_video', 'filter_poodll'), get_string('bgtranscodedetails_video', 'filter_poodll'), 0);
	$items[] = new \admin_setting_configcheckbox('filter_poodll_bgtranscode_audio', get_string('bgtranscode_audio', 'filter_poodll'), get_string('bgtranscodedetails_audio', 'filter_poodll'), 0);

	//PoodLL Whiteboard
	$items[] = new \admin_setting_heading('filter_poodll_whiteboard_setting', get_string('filter_poodll_whiteboard_heading', 'filter_poodll'), '');
	$options = array('drawingboard' => 'Drawing Board(js)', 'literallycanvas' => 'Literally Canvas(js)');
	$items[] = new \admin_setting_configselect('filter_poodll_defaultwhiteboard', get_string('defaultwhiteboard', 'filter_poodll'), '', 'literallycanvas', $options);
	$items[] = new \admin_setting_configtext('filter_poodll_whiteboardwidth', get_string('wboardwidth', 'filter_poodll'), '', '600', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_whiteboardheight', get_string('wboardheight', 'filter_poodll'), '', '350', PARAM_INT);
	$items[] = new \admin_setting_configtext('filter_poodll_autosavewhiteboard', get_string('wboardautosave', 'filter_poodll'), get_string('wboardautosave_details', 'filter_poodll'), 2000, PARAM_INT);
	
	return $items;

}// end of fetch general items

	
public static function fetch_extension_items($conf){
		//init return array
		$items = array();
		
		//add extensions csv list
		$defaultexts = implode(',',\filter_poodll\filtertools::fetch_default_extensions()); 
		$items[] = new \admin_setting_configtext('filter_poodll/extensions', 
					get_string('extensions', 'filter_poodll'),
					get_string('extensions_desc', 'filter_poodll'), 
					 $defaultexts, PARAM_RAW,70);

		//loop though extensions and offer a dropdownlist of players for each
		//get player option list
		$playeroptions = \filter_poodll\filtertools::fetch_players_list($conf);
		
		//if we have no players (could happen ...) provide something
		if(count($playeroptions) < 1){
			$playeroptions['']=get_string('none');
		}
		
		$extensions =\filter_poodll\filtertools::fetch_extensions();
		foreach($extensions as $ext){
			switch($ext){
				case 'youtube': $def_player='1';break;
				case 'rss': $def_player='1';break;
				default:
					$def_player = '1';
			}
			$items[] = new \admin_setting_configcheckbox('filter_poodll/handle' . $ext, get_string('handle', 'filter_poodll', strtoupper($ext)), '', 0);
			$items[] = new \admin_setting_configselect('filter_poodll/useplayer' . $ext, get_string('useplayer', 'filter_poodll', strtoupper($ext)),  get_string('useplayerdesc', 'filter_poodll'), $def_player, $playeroptions);
		}
		return $items;
}//end of fetch extension items

public static function fetch_widget_items(){

	$items = array();
	
	$items[]= new \admin_setting_configtext('filter_poodll/templatecount', 
				get_string('templatecount', 'filter_poodll'),
				get_string('templatecount_desc', 'filter_poodll'), 
				 filtertools::FILTER_POODLL_TEMPLATE_COUNT, PARAM_INT,20);
	return $items;

}//end of function fetch widget items

public static function fetch_template_pages($conf){
		$pages = array();

		//Add the template pages
		if($conf && property_exists($conf,'templatecount')){
			$templatecount = $conf->templatecount;
		}else{
			$templatecount = filtertools::FILTER_POODLL_TEMPLATE_COUNT;
		}
                
                //fetch preset data, just once so we do nto need to repeat the call a zillion times
                $presetdata = poodllpresets::fetch_presets();
                
		for($tindex=1;$tindex<=$templatecount;$tindex++){
		 
			 //template display name
                        $tname='';
                        if($conf && property_exists($conf,'templatename_' . $tindex)){
				$tname = $conf->{'templatename_' . $tindex};
                        }
			if(empty($tname) && $conf && property_exists($conf,'templatekey_' . $tindex)){
				$tname = $conf->{'templatekey_' . $tindex};
                        }
			if(empty($tname)){$tname=$tindex;}

		 
			 //template settings Page Settings 
			$settings_page = new \admin_settingpage('filter_poodll_templatepage_' . $tindex,get_string('templatepageheading', 'filter_poodll',$tname));
		
			//template page heading
			$settings_page->add(new \admin_setting_heading('filter_poodll/templateheading_' . $tindex, 
					get_string('templateheading', 'filter_poodll',$tname), ''));
				
			//presets
			$settings_page->add(new poodllpresets('filter_poodll/templatepresets_' . $tindex, 
					get_string('presets', 'filter_poodll'), get_string('presets_desc', 'filter_poodll'),$tindex,$presetdata));

			
                        //template name
			 $settings_page->add(new \admin_setting_configtext('filter_poodll/templatename_' . $tindex , 
					get_string('templatename', 'filter_poodll',$tindex),
					get_string('templatename_desc', 'filter_poodll'), 
					 '', PARAM_TEXT));
                        
                         //template key
			 $settings_page->add(new \admin_setting_configtext('filter_poodll/templatekey_' . $tindex , 
					get_string('templatekey', 'filter_poodll',$tindex),
					get_string('templatekey_desc', 'filter_poodll'), 
					 '', PARAM_ALPHANUMEXT));

			//template instructions
			$settings_page->add(new \admin_setting_configtextarea('filter_poodll/templateinstructions_' . $tindex,
				get_string('templateinstructions', 'filter_poodll',$tindex),
				get_string('templateinstructions_desc', 'filter_poodll'),
				'',PARAM_RAW));
				
			//template show in atto editor
			$yesno = array('0'=>get_string('no'),'1'=>get_string('yes'));
			 $settings_page->add(new \admin_setting_configselect('filter_poodll/template_showatto_' . $tindex,
					get_string('template_showatto', 'filter_poodll',$tindex),
					get_string('template_showatto_desc', 'filter_poodll'), 
					 0,$yesno));
					 
			//template show in player list
			$yesno = array('0'=>get_string('no'),'1'=>get_string('yes'));
			 $settings_page->add(new \admin_setting_configselect('filter_poodll/template_showplayers_' . $tindex,
					get_string('template_showplayers', 'filter_poodll',$tindex),
					get_string('template_showplayers_desc', 'filter_poodll'), 
					 0,$yesno));
		
			//template body
			 $settings_page->add(new \admin_setting_configtextarea('filter_poodll/template_' . $tindex,
						get_string('template', 'filter_poodll',$tindex),
						get_string('template_desc', 'filter_poodll'),''));
		
			//template body end
			 $settings_page->add(new \admin_setting_configtextarea('filter_poodll/templateend_' . $tindex,
						get_string('templateend', 'filter_poodll',$tindex),
						get_string('templateend_desc', 'filter_poodll'),''));
		
			//template defaults			
			 $settings_page->add(new \admin_setting_configtextarea('filter_poodll/templatedefaults_' . $tindex,
						get_string('templatedefaults', 'filter_poodll', $tindex),
						get_string('templatedefaults_desc', 'filter_poodll'),''));
					
			//template page JS heading
			$settings_page->add(new \admin_setting_heading('filter_poodll/templateheading_js' . $tindex, 
					get_string('templateheadingjs', 'filter_poodll',$tname), ''));
					
			//additional JS (external link)
			 $settings_page->add(new \admin_setting_configtext('filter_poodll/templaterequire_js_' . $tindex , 
					get_string('templaterequire_js', 'filter_poodll',$tindex),
					get_string('templaterequire_js_desc', 'filter_poodll'), 
					 '', PARAM_RAW,50));
				 
			//template amd
			$yesno = array('0'=>get_string('no'),'1'=>get_string('yes'));
			 $settings_page->add(new \admin_setting_configselect('filter_poodll/template_amd_' . $tindex,
					get_string('templaterequire_amd', 'filter_poodll',$tindex),
					get_string('templaterequire_amd_desc', 'filter_poodll'), 
					 1,$yesno));
					 
			//template shim
			$settings_page->add(new \admin_setting_configtext('filter_poodll/templaterequire_js_shim_' . $tindex , 
					get_string('templaterequire_js_shim', 'filter_poodll',$tindex),
					get_string('templaterequire_js_shim_desc', 'filter_poodll'), 
					 '', PARAM_TEXT,50));			
			
		
			//template body script
			 $settings_page->add(new \admin_setting_configtextarea('filter_poodll/templatescript_' . $tindex,
						get_string('templatescript', 'filter_poodll',$tindex),
						get_string('templatescript_desc', 'filter_poodll'),
						'',PARAM_RAW));
		
				 
			//template page CSS heading
			$settings_page->add(new \admin_setting_heading('filter_poodll/templateheading_css_' . $tindex, 
					get_string('templateheadingcss', 'filter_poodll',$tname), ''));
				 
			//additional CSS (external link)
			$settings_page->add(new \admin_setting_configtext('filter_poodll/templaterequire_css_' . $tindex , 
					get_string('templaterequire_css', 'filter_poodll',$tindex),
					get_string('templaterequire_css_desc', 'filter_poodll'), 
					 '', PARAM_RAW,50));
				 
			//template body css
			 $settings_page->add(new \admin_setting_configtextarea('filter_poodll/templatestyle_' . $tindex,
						get_string('templatestyle', 'filter_poodll',$tindex),
						get_string('templatestyle_desc', 'filter_poodll'),
						'',PARAM_RAW));

			//dataset
			$settings_page->add(new \admin_setting_configtextarea('filter_poodll/dataset_' . $tindex,
				get_string('dataset', 'filter_poodll',$tindex),
				get_string('dataset_desc', 'filter_poodll'),
				'',PARAM_RAW));

			//dataset vars
			$settings_page->add(new \admin_setting_configtext('filter_poodll/datasetvars_' . $tindex ,
				get_string('datasetvars', 'filter_poodll',$tindex),
				get_string('datasetvars_desc', 'filter_poodll'),
				'', PARAM_RAW,50));

			$pages[] = $settings_page;
		}

		return $pages;
	}//end of function fetch template pages

}//end of class
