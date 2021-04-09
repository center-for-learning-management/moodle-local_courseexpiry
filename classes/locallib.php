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

namespace local_courseexpiry;

defined('MOODLE_INTERNAL') || die;

class locallib {
    /**
     * Notifies all editingteachers about upcoming deletions.
     */
    public static function notify_users() {
        global $CFG, $DB;
        $timetodeletionweeks = get_config('local_courseexpiry', 'timetodeletionweeks');
        $courses = $DB->get_records('local_courseexpiry', array('status' => 1));
        $stringman = \get_string_manager();
        $fromuser = \core_user::get_support_user();
        $notified = array(); // keep notified users, we only notify each user once.
        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->id);
            $users = \get_enrolled_users($ctx, 'moodle/course:update');
            foreach ($users as $user) {
                if (!in_array($user->id, $notified)) {
                    $notified[] = $user->id;
                    $user->fullname = \fullname($user, true);
                    $user->timetodeletionweeks = $timetodeletionweeks;
                    $user->wwwroot = $CFG->wwwroot;
                    $subject = $stringman->get_string('notify:subject', 'local_courseexpiry', $user->lang, $user);
                    $messagehtml = $stringman->get_string('notify:html', 'local_courseexpiry', $user->lang, $user);
                    $messagetext = \html_to_text($messagehtml);
                    \email_to_user($user, $fromuser, $subject, $messagetext, $messagehtml, "", true);
                }
            }

        }
    }
}
