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

require_once($CFG->dirroot . '/theme/bootstrapbase/renderers.php');

/**
 * Clean core renderers.
 *
 * @package    theme_clean
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_clean_hashmal_core_renderer extends theme_bootstrapbase_core_renderer {

    /**
     * Either returns the parent version of the header bar, or a version with the logo replacing the header.
     *
     * @since Moodle 2.9
     * @param array $headerinfo An array of header information, dependant on what type of header is being displayed. The following
     *                          array example is user specific.
     *                          heading => Override the page heading.
     *                          user => User object.
     *                          usercontext => user context.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    /*public function context_header($headerinfo = null, $headinglevel = 1) {
        if ($headinglevel == 1 && !empty($this->page->theme->settings->logo)) {
            return html_writer::tag('div', '', array('class' => 'logo'));
        }
        return parent::context_header($headerinfo, $headinglevel);
    }*/

    /**
     * Either returns the parent version of the header bar, or a version with the logo replacing the header.
     * @param context_header $contextheader
     * @return string
     */
    protected function render_context_header(context_header $contextheader) {

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image');
        }

        $logo = '';
        // Big Logo, from settings.(Lea)
        if (!empty($this->page->theme->settings->logo)) {
            $alt = (!isset($contextheader->heading)) ? $this->page->heading : $contextheader->heading;
            $logo = html_writer::empty_tag('img', array('src' => $this->page->theme->setting_file_url('logo', 'logo'), 'alt' => $alt, 'class' => 'logo_img'));
        }

        // Small Logo, from settings.(Lea)
        if (!empty($this->page->theme->settings->logosmall)) {
            $alt = (!isset($contextheader->heading)) ? $this->page->heading : $contextheader->heading;
            $logo .= html_writer::empty_tag('img', array('src' => $this->page->theme->setting_file_url('logosmall', 'logosmall'), 'alt' => $alt, 'class' => 'logo_img_mob'));
        }

        // ort logo, from theme
        $ort_logo = html_writer::link('http://ort.org.il', get_string('ortsite', 'theme_clean_hashmal'), array('class' => 'logo-ort'));

        // Headings.
        if (!isset($contextheader->heading)) {
            $headings = $this->heading($logo, $contextheader->headinglevel);
        } else {
            $headings = $this->heading($logo, $contextheader->headinglevel);
        }

        $html .= html_writer::tag('div', $ort_logo . $headings, array('class' => 'page-header-headings'));

        // Top menu - from settings
        $html .= $this->get_settings_navbar();

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'message') {
                        message_messenger_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();

        return $html;
    }

    private function get_settings_navbar() {
        global $PAGE;


        $num_of_items = 5;

        $settings_items = array();

        // convert to array for easier access
        $settings_arr = (array)$PAGE->theme->settings;

        for ($i = 0; $i < $num_of_items; $i++) {
            $item = new stdClass();
            $item->id = 'item' . ($i + 1);
            $item->label_setting_name = $item->id . 'label';
            $item->url_setting_name = $item->id . 'url';

            // get the label and url
            $item->label_setting_value =
                !empty($settings_arr[$item->label_setting_name]) && !empty($settings_arr[$item->url_setting_name]) ? $settings_arr[$item->label_setting_name] : '';
            $item->url_setting_value =
                !empty($settings_arr[$item->label_setting_name]) && !empty($settings_arr[$item->url_setting_name]) ? $settings_arr[$item->url_setting_name] : '';

            //create an html element if there are values
            if (!empty($item->label_setting_value) && !empty($item->url_setting_value)) {
                $item->link = html_writer::link($item->url_setting_value, $item->label_setting_value);
                array_push($settings_items, $item->link);
            }


        }
        $str = html_writer::alist($settings_items, array('class' => 'links-navigation', 'id' => 'hashmal-links-navigation'));
        return $str;
    }
}


if (class_exists('format_flexsections')) {
    class theme_clean_hashmal_format_flexsections extends format_flexsections {
        /**
         * Course-specific information to be output immediately above content on any course page
         *
         * See {@link format_base::course_header()} for usage
         *
         * @return null|renderable null for no output or object with data for plugin renderer
         */
        public function course_content_header() {
            global $PAGE;

            // if we are on course view page for particular section, return 'back to parent' control
            /* if ($this->get_viewed_section()) {
                 $section = $this->get_section($this->get_viewed_section());
                 if ($section->parent) {
                     $sr = $this->find_collapsed_parent($section->parent);
                     $text = new lang_string('backtosection', 'format_flexsections', $this->get_section_name($section->parent));
                 } else {
                     $sr = 0;
                     $text = new lang_string('backtocourse', 'format_flexsections', $this->get_course()->fullname);
                 }
                 $url = $this->get_view_url($section->section, array('sr' => $sr));
                 return new format_flexsections_edit_control('backto', $url, strip_tags($text));
             }

             // if we are on module view page, return 'back to section' control
             if ($PAGE->context && $PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->cm) {
                 $sectionnum = $PAGE->cm->sectionnum;
                 if ($sectionnum) {
                     $text = new lang_string('backtosection', 'format_flexsections', $this->get_section_name($sectionnum));
                 } else {
                     $text = new lang_string('backtocourse', 'format_flexsections', $this->get_course()->fullname);
                 }
                 return new format_flexsections_edit_control('backto', $this->get_view_url($sectionnum), strip_tags($text));
             }*/

            //return parent::course_content_header();
        }
    }
}
