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

$string['pluginname'] = 'Course Expiry';
$string['privacy:metadata'] = 'This plugin does not store any personal data';

// $string['checkstops'] = 'Checkstops';
// $string['checkstops:description'] = 'Specifies dates when courses should be checked for expiry. Enter line by line in mmdd-format.';

$string['delete'] = 'Delete';

$string['excludedremoval'] = 'Excluded from removal';
$string['expired_courses'] = 'Expired Courses';
$string['expired_courses:explanation'] = 'The following list shows all expired courses, that will be deleted soon. Please uncheck all courses, that you want to keep.';

$string['ignorecategories'] = 'Ignore Categories';
$string['ignorecategories:description'] = 'Enter here categories, that should be ignored by this plugin. Delimit multiple ids by a comma.';
$string['ignorecourses'] = 'Ignore Courses';
$string['ignorecourses:description'] = 'Enter here courseids, that should be ignored by this plugin. Delimit multiple ids by a comma.';

$string['keep'] = 'Keep';

$string['listempty'] = 'No courses are scheduled for deletion';

$string['notify:subject'] = 'Courses are expired';
$string['notify:html'] = '
    <h3>Dear {$a->fullname}</h3>
    <p>
        We recently recognized, that some certain courses are expired, in which you
        are enrolled as editing teacher. All expired courses will be deleted
        permanently after {$a->timetodeletionweeks} weeks.
    </p>
    <p>
        If you want to keep any courses please go to the provided list and
        check all courses you want to keep.
    </p>
    <p>
        <a href="{$a->wwwroot}/local/courseexpiry/expiredcourses.php">
            {$a->wwwroot}/local/courseexpiry/expiredcourses.php
        </a>
    </p>';

$string['status'] = 'Status';
$string['scheduledremoval'] = 'Scheduled for removal';

$string['task:check_courses'] = 'Mark courses for deletion';
$string['task:hide_courses'] = 'Hide courses';
$string['task:delete_courses'] = 'Delete courses';
$string['expire_time'] = 'Course end date';
$string['expire_time:description'] = 'Specifies the time when a course is considered expired. This is used to determine when a course should be marked for deletion.';
$string['hide_courses_categoryid'] = 'Hide courses category ID';
$string['hide_courses_categoryid:description'] = 'This is the category ID where courses will be moved to when they are hidden.';
$string['timedelete'] = 'Day of deletion';
$string['timetodeletion'] = 'Time to deletion';
$string['timetodeletion:description'] = 'Specifies how many weeks the task will wait before courses are really deleted.';
$string['timetodeletionweeks_immediate'] = 'immediately';
$string['timetodeletionweeks_single'] = '1 week';
$string['timetodeletionweeks'] = '{$a->weeks} weeks';

$string['backupdir'] = 'Backup directory';
$string['backupdir:description'] = 'The directory where backups of courses are stored. This is used to restore courses that were deleted by this plugin.';
