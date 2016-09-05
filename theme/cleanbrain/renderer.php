<?php
/**
 * Created by PhpStorm.
 * User: mirik
 * Date: 06/08/14
 * Time: 10:19
 */

class cleanbrain_format_grid_renderer extends format_grid_renderer {

    private $topic0_at_top; // Boolean to state if section zero is at the top (true) or in the grid (false).
    private $courseformat; // Our course format object as defined in lib.php.
    private $shadeboxshownarray = array(); // Value of 1 = not shown, value of 2 = shown - to reduce ambiguity in JS.


    private function make_block_icon_topics($contextid, $modinfo, $course, $editing, $hascapvishidsect,
                                        $urlpicedit) {
    global $USER, $CFG;

    $currentlanguage = current_language();
    if (!file_exists("$CFG->dirroot/course/format/grid/pix/new_activity_" . $currentlanguage . ".png")) {
        $currentlanguage = 'en';
    }
    $url_pic_new_activity = $this->output->pix_url('new_activity_' . $currentlanguage, 'format_grid');

    if ($editing) {
        $streditimage = get_string('editimage', 'format_grid');
        $streditimagealt = get_string('editimage_alt', 'format_grid');
    }

    // Get all the section information about which items should be marked with the NEW picture.
    $sectionupdated = $this->new_activity($course);

    // Get the section images for the course.
    $sectionimages = $this->courseformat->get_images($course->id);

    // CONTRIB-4099:...
    $gridimagepath = $this->courseformat->get_image_path();

    // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
    for ($section = $this->topic0_at_top ? 1 : 0; $section <= $course->numsections; $section++) {
        $thissection = $modinfo->get_section_info($section);

        // Check if section is visible to user.
        $showsection = $hascapvishidsect || ($thissection->visible && ($thissection->available ||
                    $thissection->showavailability || !$course->hiddensections));

        if ($showsection) {
            // We now know the value for the grid shade box shown array.
            $this->shadeboxshownarray[$section] = 2;

            $sectionname = $this->courseformat->get_section_name($thissection);

            /* Roles info on based on: http://www.w3.org/TR/wai-aria/roles.
               Looked into the 'grid' role but that requires 'row' before 'gridcell' and there are none as the grid
               is responsive, so as the container is a 'navigation' then need to look into converting the containing
               'div' to a 'nav' tag (www.w3.org/TR/2010/WD-html5-20100624/sections.html#the-nav-element) when I'm
               that all browsers support it against the browser requirements of Moodle. */
            $liattributes = array(
                'role' => 'region',
                'aria-label' => $sectionname
            );
            if ($this->courseformat->is_section_current($section)) {
                $liattributes['class'] = 'current';
            }
            echo html_writer::start_tag('li', $liattributes);

            // Ensure the record exists.
            if  (($sectionimages === false) || (!array_key_exists($thissection->id, $sectionimages))) {
                // get_image has 'repair' functionality for when there are issues with the data.
                $sectionimage = $this->courseformat->get_image($course->id, $thissection->id);
            } else {
                $sectionimage = $sectionimages[$thissection->id];
            }

            // If the image is set then check that displayedimageindex is greater than 0 otherwise create the displayed image.
            // This is a catch-all for existing courses.
            if (isset($sectionimage->image) && ($sectionimage->displayedimageindex < 1)) {
                // Set up the displayed image:...
                $sectionimage->newimage = $sectionimage->image;
                $sectionimage = $this->courseformat->setup_displayed_image($sectionimage, $contextid,
                    $this->courseformat->get_settings());
                if (format_grid::is_developer_debug()) {
                    error_log('make_block_icon_topics: Updated displayed image for section ' . $thissection->id . ' to ' .
                        $sectionimage->newimage . ' and index ' . $sectionimage->displayedimageindex);
                }
            }

            if ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                echo html_writer::start_tag('a', array(
                    'href' => '#section-' . $thissection->section,
                    'id' => 'gridsection-' . $thissection->section,
                    'class' => 'gridicon_link',
                    'role' => 'link',
                    'aria-label' => $sectionname));

                echo html_writer::tag('p', $sectionname, array('class' => 'icon_content'));

                if (isset($sectionupdated[$thissection->id])) {
                    // The section has been updated since the user last visited this course, add NEW label.
                    echo html_writer::empty_tag('img', array(
                        'class' => 'new_activity',
                        'src' => $url_pic_new_activity,
                        'alt' => ''));
                }

                echo html_writer::start_tag('div', array('class' => 'image_holder'));

                $showimg = false;
                if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
                    $imgurl = moodle_url::make_pluginfile_url(
                        $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                        $sectionimage->displayedimageindex . '_' . $sectionimage->image);
                    $showimg = true;
                } else if ($section == 0) {
                    $imgurl = $this->output->pix_url('info', 'format_grid');
                    $showimg = true;
                }
                if ($showimg) {
                    echo html_writer::empty_tag('img', array(
                        'src' => $imgurl,
                        'alt' => $sectionname,
                        'role' => 'img',
                        'aria-label' => $sectionname));
                }

                echo html_writer::end_tag('div');
                echo html_writer::end_tag('a');

                if ($editing) {
                    echo html_writer::link(
                        $this->courseformat->grid_moodle_url('editimage.php', array(
                            'sectionid' => $thissection->id,
                            'contextid' => $contextid,
                            'userid' => $USER->id,
                            'role' => 'link',
                            'aria-label' => $streditimagealt)), html_writer::empty_tag('img', array(
                            'src' => $urlpicedit,
                            'alt' => $streditimagealt,
                            'role' => 'img',
                            'aria-label' => $streditimagealt)) . '&nbsp;' . $streditimage,
                        array('title' => $streditimagealt));

                    if ($section == 0) {
                        $strdisplaysummary = get_string('display_summary', 'format_grid');
                        $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

                        echo html_writer::empty_tag('br') . html_writer::link(
                                $this->courseformat->grid_moodle_url('mod_summary.php', array(
                                    'sesskey' => sesskey(),
                                    'course' => $course->id,
                                    'showsummary' => 1,
                                    'role' => 'link',
                                    'aria-label' => $strdisplaysummaryalt)), html_writer::empty_tag('img', array(
                                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                                    'alt' => $strdisplaysummaryalt,
                                    'role' => 'img',
                                    'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary, array('title' => $strdisplaysummaryalt));
                    }
                }
                echo html_writer::end_tag('li');
            } else {
                $title = html_writer::tag('p', $sectionname, array('class' => 'icon_content'));

                if (isset($sectionupdated[$thissection->id])) {
                    $title .= html_writer::empty_tag('img', array(
                        'class' => 'new_activity',
                        'src' => $url_pic_new_activity,
                        'alt' => ''));
                }

                $title .= html_writer::start_tag('div', array('class' => 'image_holder'));

                $showimg = false;
                if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
                    $imgurl = moodle_url::make_pluginfile_url(
                        $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                        $sectionimage->displayedimageindex . '_' . $sectionimage->image);
                    $showimg = true;
                } else if ($section == 0) {
                    $imgurl = $this->output->pix_url('info', 'format_grid');
                    $showimg = true;
                }
                if ($showimg) {
                    $title .= html_writer::empty_tag('img', array(
                        'src' => $imgurl,
                        'alt' => $sectionname,
                        'role' => 'img',
                        'aria-label' => $sectionname));
                }

                $title .= html_writer::end_tag('div');

                $url = course_get_url($course, $thissection->section);
                if ($url) {
                    $title = html_writer::link($url, $title, array(
                        'id' => 'gridsection-' . $thissection->section,
                        'role' => 'link',
                        'aria-label' => $sectionname));
                }
                echo $title;

                if ($editing) {
                    echo html_writer::link(
                        $this->courseformat->grid_moodle_url('editimage.php', array(
                            'sectionid' => $thissection->id,
                            'contextid' => $contextid,
                            'userid' => $USER->id,
                            'role' => 'link',
                            'aria-label' => $streditimagealt)), html_writer::empty_tag('img', array(
                            'src' => $urlpicedit,
                            'alt' => $streditimagealt,
                            'role' => 'img',
                            'aria-label' => $streditimagealt)) . '&nbsp;' . $streditimage,
                        array('title' => $streditimagealt));

                    if ($section == 0) {
                        $strdisplaysummary = get_string('display_summary', 'format_grid');
                        $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

                        echo html_writer::empty_tag('br') . html_writer::link(
                                $this->courseformat->grid_moodle_url('mod_summary.php', array(
                                    'sesskey' => sesskey(),
                                    'course' => $course->id,
                                    'showsummary' => 1,
                                    'role' => 'link',
                                    'aria-label' => $strdisplaysummaryalt)), html_writer::empty_tag('img', array(
                                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                                    'alt' => $strdisplaysummaryalt,
                                    'role' => 'img',
                                    'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                                array('title' => $strdisplaysummaryalt));
                    }
                }
                echo html_writer::end_tag('li');
            }
        } else {
            // We now know the value for the grid shade box shown array.
            $this->shadeboxshownarray[$section] = 1;
        }
    }
}
}