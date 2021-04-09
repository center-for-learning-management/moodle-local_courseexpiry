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
     * Checks for expired courses.
     * @param debug show debug output.
     */
    public static function check_expiry($debug = false) {
        global $DB;

        $sql = "SELECT id
                    FROM {course}
                    WHERE id NOT IN (
                        SELECT courseid
                            FROM {local_courseexpiry}
                    )";
        $newcourses = $DB->get_records_sql($sql, array());
        $cnt = 0;
        foreach ($newcourses as $newcourse) {
            $cnt++;
            $DB->insert_record('local_courseexpiry', array(
                'courseid' => $newcourse->id,
                'status' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'timedelete' => 0,
            ));
        }
        if ($debug) {
            echo "Added $cnt courses to local_courseexpiry<br />";
        }

        $checkstops = explode("\n", get_config('local_courseexpiry', 'checkstops'));
        $mmdd = date("md");
        if (in_array($mmdd, $checkstops)) {
            if ($debug) {
                echo "Update local_courseexpiry and schedule deletion of expired courses<br />";
            }

            $sql = "SELECT id
                        FROM {course}
                        WHERE enddate > 0
                            AND enddate < ?";


            $expiredcourseids = array_keys($DB->get_records_sql($sql, array(time())));
            if (count($expiredcourseids) > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($expiredcourseids);
                $inparams = array_merge(
                    array(
                        time(),
                        strtotime('+' . get_config('local_courseexpiry', 'timetodeletionweeks') . ' week'),
                    ),
                    $inparams,
                    array(
                        time()
                    )
                );

                $sql = "UPDATE {local_courseexpiry}
                            SET status = 1, timemodified = ?, timedelete = ?
                            WHERE courseid $insql
                                AND timedelete < ?";

                $DB->execute($sql, $inparams);
            }
        }

        $sql = "SELECT *
                    FROM {local_courseexpiry}
                    WHERE status = ? AND timedelete < ?";
        $params = array(
            1,
            time()
        );
        $deletecourses = $DB->get_records_sql($sql, $params);
        echo count($deletecourses) . " courses need to be deleted<br />";
        foreach ($deletecourses as $deletecourse) {
            if ($debug) {
                echo "Remove course #$deletecourse->id<br />";
            }
            \delete_course($deletecourse->courseid, false);
            $DB->delete_records('local_courseexpiry', array('courseid' => $deletecourse->id));
        }

        if (in_array($mmdd, $checkstops)) {
            \local_courseexpiry\locallib::notify_users($debug);
        }
    }
    /**
     * Notifies all editingteachers about upcoming deletions.
     * @param debug show debug output.
     */
    public static function notify_users($debug = false) {
        global $CFG, $DB;
        if ($debug) {
            echo "Notify users.<br />";
        }

        $timetodeletionweeks = get_config('local_courseexpiry', 'timetodeletionweeks');
        $courses = $DB->get_records('local_courseexpiry', array('status' => 1));
        $fromuser = \core_user::get_support_user();
        $notified = array(); // keep notified users, we only notify each user once.
        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->courseid, 'IGNORE_MISSING');
            if (empty($ctx->id)) {
                // This entry must have been kept by accident. Course has already been removed.
                $DB->delete_records('local_courseexpiry', array('courseid' => $course->courseid));
                continue;
            }
            $users = \get_enrolled_users($ctx, 'moodle/course:update');
            foreach ($users as $user) {
                if (!in_array($user->id, $notified)) {
                    $notified[] = $user->id;
                    $user->fullname = \fullname($user, true);
                    $user->timetodeletionweeks = $timetodeletionweeks;
                    $user->wwwroot = $CFG->wwwroot;
                    $subject = get_string('notify:subject', 'local_courseexpiry', $user->lang, $user);
                    $messagehtml = get_string('notify:html', 'local_courseexpiry', $user->lang, $user);
                    $messagetext = \html_to_text($messagehtml);
                    \email_to_user($user, $fromuser, $subject, $messagetext, $messagehtml, "", true);
                    if ($debug) {
                        echo "=> Sent notification to user $user->fullname<br />";
                    }
                }
            }
        }
    }
}
