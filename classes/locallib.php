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
    public static function check_expiry($debug = false, $task = false, $dryrun = false) {
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
            self::output("Added $cnt courses to local_courseexpiry", $task);
        }

        $checkstops = explode("\n", get_config('local_courseexpiry', 'checkstops'));
        $mmdd = date("md");
        if (in_array($mmdd, $checkstops) || $dryrun) {
            if ($debug) {
                self::output("Update local_courseexpiry and schedule deletion of expired courses", $task);
            }

            $ignorecategories = explode(',', get_config('local_courseexpiry', 'ignorecategories'));
            $ignorecourses = explode(',', get_config('local_courseexpiry', 'ignorecourses'));

            $sql = "SELECT id
                        FROM {course}
                        WHERE enddate > 0
                            AND enddate < ?";

            $_expiredcourseids = array_keys($DB->get_records_sql($sql, array(time())));
            $expiredcourseids = array();
            foreach ($_expiredcourseids as $courseid) {
                if (count($ignorecourses) > 0 && in_array($courseid, $ignorecourses)) {
                    continue;
                }

                $ignorecourse = false;
                if (count($ignorecategories) > 0) {
                    $ctx = \context_course::instance($courseid);
                    // Remove courseid from path for comparison.
                    $path = substr($ctx->path, 0, strrpos($ctx->path, '/')) . '/';
                    foreach ($ignorecategories as $cat) {
                        if (str_contains($path, '/' . $cat . '/')) {
                            $ignorecourse = true;
                            break;
                        }
                    }
                }

                if ($ignorecourse) {
                    continue;
                }

                $expiredcourseids[] = $courseid;
            }

            if ($dryrun) {
                return $expiredcourseids;
            }

            if (count($expiredcourseids) > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($expiredcourseids);
                $inparams = [
                    time(),
                    strtotime('+' . get_config('local_courseexpiry', 'timetodeletionweeks') . ' week'),
                    ...$inparams,
                    time(),
                ];
                $sql = "UPDATE {local_courseexpiry}
                            SET status = 1, timemodified = ?, timedelete = ?
                            WHERE courseid $insql
                                AND timedelete < ?";

                $DB->execute($sql, $inparams);
            }
        }

        if ($dryrun) {
            throw new \moodle_exception('should not happen');
        }

        // Now select all courses that should be deleted.
        $sql = "SELECT *
                    FROM {local_courseexpiry}
                    WHERE status = ? AND timedelete < ?";
        $params = array(
            1,
            time(),
        );
        $deletecourses = $DB->get_records_sql($sql, $params);
        if ($debug) {
            self::output(count($deletecourses) . " courses need to be deleted", $task);
        }
        foreach ($deletecourses as $deletecourse) {
            if ($debug) {
                self::output("Remove course #$deletecourse->courseid of entry #$deletecourse->id", $task);
            }
            \delete_course($deletecourse->courseid, false);
            $DB->delete_records('local_courseexpiry', array('courseid' => $deletecourse->courseid));
        }
        // Now remove the delete-flag of those entries, that should be kept instead of deleted.
        $sql = "UPDATE {local_courseexpiry}
                    SET timedelete = 0
                    WHERE timedelete < ?
                        AND status = 0";
        $params = [
            time(),
        ];
        $DB->execute($sql, $params);

        if (in_array($mmdd, $checkstops)) {
            \local_courseexpiry\locallib::notify_users($debug);
        }
    }

    /**
     * Return all courses of a user that are expired.
     */
    public static function get_expired_courses() {
        global $DB, $USER;
        $usercourses = \enrol_get_all_users_courses($USER->id, true);

        if (count($usercourses) == 0)
            return $usercourses;
        $editingcourseids = array();
        foreach ($usercourses as $usercourse) {
            $ctx = \context_course::instance($usercourse->id);
            if (has_capability('moodle/course:update', $ctx, $USER, false)) {
                $editingcourseids[] = $usercourse->id;
            }
        }

        if (count($editingcourseids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($editingcourseids);
            $sql = "SELECT c.id,c.fullname,ce.status,ce.timedelete
                        FROM {course} c, {local_courseexpiry} ce
                        WHERE c.id = ce.courseid
                            AND timedelete > 0
                            AND c.id $insql
                        ORDER BY c.id";
            $courses = array_values($DB->get_records_sql($sql, $inparams));
            return $courses;
        }
        return [];
    }

    /**
     * Check all moodle courses for expiry and return them
     */
    public static function get_expired_courses_admin(): array {
        global $DB;

        $courseids = static::check_expiry(dryrun: true);

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, onemptyitems: -1);
        $sql = "SELECT c.id,c.fullname,ce.status,ce.timedelete
                        FROM {course} c, {local_courseexpiry} ce
                        WHERE c.id = ce.courseid
                            AND c.id $insql
                        ORDER BY c.id";
        $courses = array_values($DB->get_records_sql($sql, $inparams));

        return $courses;
    }

    /**
     * Get the timestamp when the last course will be deleted.
     * @param courses list of courses retrieved by self::get_expired_courses()
     */
    public static function get_lasttimedelete($courses = array()) {
        $lasttimedelete = 0;
        if (count($courses) == 0) {
            $courses = self::get_expired_courses();
        }

        foreach ($courses as $course) {
            if ($lasttimedelete < $course->timedelete) {
                $lasttimedelete = $course->timedelete;
            }
        }
        return $lasttimedelete;
    }

    private static function output($text, $task) {
        if ($task) {
            mtrace($text);
        } else {
            echo "$text<br />";
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
        $stringman = get_string_manager();
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
                    $subject = $stringman->get_string('notify:subject', 'local_courseexpiry', $user, $user->lang);
                    $messagehtml = $stringman->get_string('notify:html', 'local_courseexpiry', $user, $user->lang);
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
