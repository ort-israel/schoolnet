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
 * Administration settings definitions for the qcreate module.
 *
 * @package   mod_qcreate
 * @copyright 2014 Jean-Michel Vedrine
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $name = new lang_string('sendgradernotifications', 'mod_qcreate');
    $description = new lang_string('sendgradernotifications_help', 'mod_qcreate');
    $setting = new admin_setting_configcheckbox('qcreate/sendgradernotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $settings->add($setting);

    $name = new lang_string('sendstudentnotifications', 'mod_qcreate');
    $description = new lang_string('sendstudentnotifications_help', 'mod_qcreate');
    $setting = new admin_setting_configcheckbox('qcreate/sendstudentnotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $settings->add($setting);
}
