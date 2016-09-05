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
 * The two column layout.
 *
 * @package   theme_clean_finance
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Get the HTML for the settings bits.
$html = theme_clean_finance_get_html_for_settings($OUTPUT, $PAGE);

$left = (!right_to_left());  // To know if to add 'pull-right' and 'desktop-first-column' classes in the layout for LTR.
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes('two-column'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<header role="banner" class="navbar <?php echo $html->navbarclass ?> moodle-has-zindex tree-bg">
	<!--Tsofiya 16/2/2015: add an upgrade message for ie9 and lower -->
    <!--[if lte IE 9 ]>
    <a href="http://updateyourbrowser.net/" title="Update Your Browser"><img src="<?php echo $CFG->wwwroot; ?>/theme/clean_finance/pix/browser.png" border="0" alt="Update Your Browser" /></a>
    <![endif]-->
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <!-- Tsofiya 30/12/14: remove default nav and add required logos
            <a class="brand" href="<?php echo $CFG->wwwroot;?>"><?php echo $SITE->shortname; ?></a>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <div class="nav-collapse collapse">
                <?php echo $OUTPUT->custom_menu(); ?>
                <ul class="nav pull-right">
                    <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                    <li class="navbar-text"><?php echo $OUTPUT->login_info() ?></li>
                </ul>
            </div>-->
            <div class="logo-catch-cash-wrapper">
                <a class="logo-catch-cash" target="_blank" href="http://www.catch-cash.ort.org.il">catch cash</a>
                <a class="under-logo" target="_blank" href="http://www.catch-cash.ort.org.il"> <?php echo get_string('back-to-catch-cash',"theme_clean_finance"); ?> </a>
            </div>
            <a class="logo-bank" target="_blank" href="https://www.bankhapoalim.co.il/">bank hapoalim</a>
            <a class="logo-ort" target="_blank" href="http://ort.org.il/">ort israel</a>
        </div>
    </nav>
</header>

<div id="page" class="container-fluid">

    <header id="page-header" class="clearfix">
        <!-- Tsofiya 7/1/14: if user can't use the editing button hide it -->
        <div id="page-navbar" class="clearfix <?php echo strlen($PAGE->button)==0?'hide':''; ?> ">
            <!-- Tsofiya 7/1/14: remove breadcrumbs
            <nav class="breadcrumb-nav"><?php echo $OUTPUT->navbar(); ?></nav>
            -->
            <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
        </div>
        <div id="course-header">
            <?php echo $OUTPUT->course_header(); ?>
        </div>
    </header>

    <div id="page-content" class="row-fluid">
        <!-- Tsofiya 7/1/14: replace the display order of 'span3' & 'span9' to fix design -->
        <?php
        $classextra = '';
        if ($left) {
            $classextra = ' desktop-first-column';
        }
        echo $OUTPUT->blocks('side-pre', 'span3'.$classextra);
        ?>
        <section id="region-main" class="span9<?php if ($left) { echo ' pull-right'; } ?>">

            <?php
            /* Tsofiya 8/1/14: remove "echo $html->heading;" to here from "#page-header" */
            echo $html->heading;
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>

    </div>

    <footer id="page-footer">
        <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
        <p class="helplink"><?php echo $OUTPUT->page_doc_link(); ?></p>
        <?php
        echo $html->footnote;
        echo $OUTPUT->login_info();
        echo $OUTPUT->home_link();
        echo $OUTPUT->standard_footer_html();
        ?>
    </footer>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
