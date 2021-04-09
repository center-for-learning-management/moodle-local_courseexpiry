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
 * @package    local_courseexpiry
 * @copyright  2021 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage( 'local_courseexpiry_settings', get_string('pluginname:settings', 'local_courseexpiry'));
    $ADMIN->add('localplugins', new admin_category('local_courseexpiry', get_string('pluginname', 'local_courseexpiry')));
    $ADMIN->add('local_courseexpiry', $settings);

    // Set times in mmdd-Format when a notification should be sent.
    $settings->add(new admin_setting_configtextarea('local_courseexpiry/checkstops', get_string('checkstops', 'local_courseexpiry'), get_string('checkstops:description', 'local_courseexpiry'), "0901\n0201", PARAM_TEXT));

    // Set parameters how to determine expired courses.

    // Set parameters to exclude courses from expiration.

    // Set parameter how long teachers have time to opt out from deletion.
}
