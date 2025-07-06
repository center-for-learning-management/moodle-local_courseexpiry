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

require_once('../../config.php');
require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/courseexpiry/expiredcourses.php', array());
$PAGE->set_title(get_string('expired_courses', 'local_courseexpiry'));
$PAGE->set_heading(get_string('expired_courses', 'local_courseexpiry'));

$PAGE->navbar->add(get_string('expired_courses', 'local_courseexpiry'), $PAGE->url);

echo $OUTPUT->header();

if (is_siteadmin()) {
    $showall = optional_param('showall', 0, PARAM_BOOL);
} else {
    $showall = false;
}

if ($showall) {
    $courses = \local_courseexpiry\locallib::get_expired_courses_admin();
    foreach ($courses as $course) {
        if (!$course->timedelete) {
            // not yet marked for deletion, then set status to be deleted to 1 (to display the course as being deleted in the list).
            $course->status = 1;
        }
    }
} else {
    $courses = \local_courseexpiry\locallib::get_expired_courses();
}

$lasttimedelete = \local_courseexpiry\locallib::get_lasttimedelete($courses);
\set_user_preference('block_courseexpiry_minimizeuntil', $lasttimedelete);

if (is_siteadmin() && !$showall) {
    echo '<div class="mb-3">';
    echo '<a href="' . new moodle_url('/local/courseexpiry/expiredcourses.php', array('showall' => 1)) . '" class="btn btn-secondary">' .
        'Show all Moodle Courses' . '</a>';
    echo '</div>';
}

$params = array(
    'courses' => $courses,
    'wwwroot' => $CFG->wwwroot,
);
echo $OUTPUT->render_from_template('local_courseexpiry/expiredcourses', $params);

echo $OUTPUT->footer();
