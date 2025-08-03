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
    $ADMIN->add('localplugins', new admin_category('local_courseexpiry', get_string('pluginname', 'local_courseexpiry')));
    $settings = new admin_settingpage('local_courseexpiry_settings', get_string('pluginname', 'local_courseexpiry'));

    if ($ADMIN->fulltree) {
        // Set times in mmdd-Format when a notification should be sent.
        // By default this is at 1st of September and 1st of February.
        $settings->add(
            new admin_setting_configtextarea(
                'local_courseexpiry/checkstops',
                get_string('checkstops', 'local_courseexpiry'),
                get_string('checkstops:description', 'local_courseexpiry'),
                "0901\n0201",
                PARAM_TEXT
            )
        );

        // Set parameter how long teachers have time to opt out from deletion.
        $ranges = array();
        $ranges[0] = get_string('timetodeletionweeks_immediate', 'local_courseexpiry');
        $ranges[1] = get_string('timetodeletionweeks_single', 'local_courseexpiry');
        for ($a = 2; $a < 10; $a++) {
            $ranges[$a] = get_string('timetodeletionweeks', 'local_courseexpiry', array('weeks' => $a));
        }

        $settings->add(
            new admin_setting_configselect(
                'local_courseexpiry/timetodeletionweeks',
                get_string('timetodeletion', 'local_courseexpiry'),
                get_string('timetodeletion:description', 'local_courseexpiry'),
                4,
                $ranges
            )
        );

        $ignorecategories = explode(',', get_config('local_courseexpiry', 'ignorecategories'));
        sort($ignorecategories);
        if (is_array($ignorecategories) && count($ignorecategories) > 0) {
            set_config('local_courseexpiry', 'ignorecategories', implode(',', $ignorecategories));
        }
        $settings->add(
            new admin_setting_configtextarea(
                'local_courseexpiry/ignorecategories',
                get_string('ignorecategories', 'local_courseexpiry'),
                get_string('ignorecategories:description', 'local_courseexpiry'),
                "",
                PARAM_TEXT
            )
        );
        $ignorecourses = explode(',', get_config('local_courseexpiry', 'ignorecourses'));
        sort($ignorecourses);
        if (is_array($ignorecourses) && count($ignorecourses) > 0) {
            set_config('local_courseexpiry', 'ignorecourses', implode(',', $ignorecourses));
        }
        $settings->add(
            new admin_setting_configtextarea(
                'local_courseexpiry/ignorecourses',
                get_string('ignorecourses', 'local_courseexpiry'),
                get_string('ignorecourses:description', 'local_courseexpiry'),
                "",
                PARAM_TEXT
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'local_courseexpiry/backupdir',
                get_string('backupdir', 'local_courseexpiry'),
                get_string('backupdir:description', 'local_courseexpiry'),
                "",
                PARAM_TEXT
            )
        );
    }

    $ADMIN->add('local_courseexpiry', $settings);
}
