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

$courses = \local_courseexpiry\locallib::get_expired_courses();
$params = array(
    'courses' => $courses,
    'wwwroot' => $CFG->wwwroot,
);
echo $OUTPUT->render_from_template('local_courseexpiry/expiredcourses', $params);

echo $OUTPUT->footer();
