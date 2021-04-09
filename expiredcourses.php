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
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/courseexpiry/expiredcourses.php', array());
$PAGE->set_title(get_string('expired_courses', 'local_courseexpiry'));
$PAGE->set_heading(get_string('expired_courses', 'local_courseexpiry'));

echo $OUTPUT->header();

$usercourses = \enrol_get_all_users_courses($USER->id, true);
$editingcourseids = array();
foreach ($usercourses as $usercourse) {
    $ctx = \context_course::instance($usercourse->id);
    if (has_capability('moodle/course:update', $ctx, $USER, false)) {
        $editingcourseids[] = $usercourse->id;
    }
}

list($insql, $inparams) = $DB->get_in_or_equal($editingcourseids);
$sql = "SELECT c.id,c.fullname,ce.status,ce.timedelete
            FROM {course} c, {local_courseexpiry} ce
            WHERE c.id = ce.courseid
                AND timedelete > 0
                AND c.id $insql";
$courses = array_values($DB->get_records_sql($sql, $inparams));

$params = array(
    'courses' => $courses,
    'wwwroot' => $CFG->wwwroot,
);
echo $OUTPUT->render_from_template('local_courseexpiry/expiredcourses', $params);

echo $OUTPUT->footer();
