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

use backup_controller;
use backup;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

class locallib {
    static $is_task = false;

    static function set_is_task(bool $is_task): void {
        self::$is_task = $is_task;
    }

    public static function get_expired_courseids(): array {
        global $DB;

        $ignorecategories = array_filter(explode(',', get_config('local_courseexpiry', 'ignorecategories')));
        $ignorecourses = array_filter(explode(',', get_config('local_courseexpiry', 'ignorecourses')));
        $ignorecontexts = [];
        foreach ($ignorecategories as $categoryid) {
            try {
                $ignorecontexts[$categoryid] = \context_coursecat::instance($categoryid)->path;
            } catch (\moodle_exception $e) {
                // Category does not exist, skip it.
                continue;
            }
        }

        $_expiredcourses = $DB->get_records_sql("SELECT id, enddate, fullname
                    FROM {course}
                    WHERE enddate < ?
                    AND id > 1 -- ignore site course", [static::get_expired_time()]);
        $expiredcourseids = array();
        foreach ($_expiredcourses as $courseid => $course) {
            if (count($ignorecourses) > 0 && in_array($courseid, $ignorecourses)) {
                continue;
            }

            if (str_starts_with($course->fullname, 'Helpdesk') || str_starts_with($course->fullname, 'Digitaler Schulhof')) {
                continue;
            }

            $ignorecourse = false;
            if (count($ignorecontexts) > 0) {
                $ctx = \context_course::instance($courseid);

                foreach ($ignorecontexts as $path) {
                    if (str_starts_with($ctx->path, $path)) {
                        $ignorecourse = true;
                        break;
                    }
                }
            }
            if ($ignorecourse) {
                continue;
            }

            if (!$course->enddate) {
                $ctx = \context_course::instance($course->id);
                $users = \get_enrolled_users($ctx);
                if ($users) {
                    continue;
                }

                // if ($debug) {
                //     self::output("Course #{$course->id} \"{$course->fullname}\" has no users and will be deleted in future versions, for now the course is ignored.", $task);
                // }
                // continue;
            }

            $expiredcourseids[] = $courseid;
        }

        return $expiredcourseids;
    }

    public static function check_expiry(): void {
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

        self::output("Added $cnt courses to local_courseexpiry");

        /*
        $checkstops = explode("\n", get_config('local_courseexpiry', 'checkstops'));
        $mmdd = date("md");
        if (!in_array($mmdd, $checkstops) && !$dryrun) {
            self::output("Today is not a day to mark courses for deletion");
            return;
        }
        */

        $expiredcourseids = static::get_expired_courseids();

        if (count($expiredcourseids) > 0) {
            self::output("Update local_courseexpiry and schedule deletion of expired courses");

            list($insql, $inparams) = $DB->get_in_or_equal($expiredcourseids);
            $inparams = [
                time(),
                strtotime('+' . get_config('local_courseexpiry', 'timetodeletionweeks') . ' week'),
                ...$inparams,
            ];
            $sql = "UPDATE {local_courseexpiry}
                            SET status = 1, timemodified = ?, timedelete = ?
                            WHERE status=0 AND courseid $insql";

            $DB->execute($sql, $inparams);
        }

        self::output("Mark running/future courses as not deleted");
        [$courseid_sql, $courseid_params] = $DB->get_in_or_equal($expiredcourseids, equal: false, onemptyitems: null);
        $sql = "UPDATE {local_courseexpiry}
            SET status = 0, timemodified = ?, timedelete = 0, timeusersnotified = 0
            WHERE status=1 AND courseid $courseid_sql";
        $DB->execute($sql, [time(), ...$courseid_params]);

        // delete old course_expiry entries
        $DB->execute('DELETE FROM {local_courseexpiry} WHERE courseid NOT IN (SELECT id FROM {course})');
    }

    public static function backup_course(object $course, string $backupdir): string {
        global $DB, $USER;

        // Ensure the backup directory exists
        if (!is_dir($backupdir)) {
            mkdir($backupdir, 0777, true);
        }
        if (!is_dir($backupdir)) {
            throw new \moodle_exception('backupdir could not be created', 'local_courseexpiry', '', $backupdir);
        }
        if (!is_writable($backupdir)) {
            throw new \moodle_exception('backupdir not writable', 'local_courseexpiry', '', $backupdir);
        }

        // Get the course category
        $category = $DB->get_record('course_categories', ['id' => $course->category], '*', MUST_EXIST);
        // Traverse up to the top-level category
        while ($category->parent != 0) {
            $category = $DB->get_record('course_categories', ['id' => $category->parent], '*', MUST_EXIST);
        }

        $clean = fn($string) => trim(substr(preg_replace('![^a-z0-9]+!i', '_', $string), 0, 40), '_');

        $extra_title = 'Wird gelöscht: ';

        $parts = [
            'course_backup',
            'courseid',
            $course->id,
            'cat_idnumber',
            $clean($category->idnumber ?: 'none'),
            'cat_name',
            $clean($category->name ?: 'none'),
            'fullname',
            $clean(str_replace($extra_title, '', $course->fullname)),
            date('Ymd_His'),
        ];

        // Set the backup file name
        $backupfile = $backupdir . '/' . join('-', $parts) . '.mbz';

        // Create a backup controller
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );

        // Execute the backup plan
        $bc->execute_plan();

        $results = $bc->get_results();
        /* @var \stored_file $backupfile */
        $stored_file = $results['backup_destination'];

        $stored_file->copy_content_to($backupfile);
        $stored_file->delete();

        // Cleanup
        $bc->destroy();

        if (!is_file($backupfile)) {
            throw new \moodle_exception('backupfile not created', 'local_courseexpiry', '', $backupfile);
        }

        return $backupfile;
    }

    public static function hide_courses() {
        global $DB;

        $hide_courses_category = $DB->get_record('course_categories', ['id' => get_config('local_courseexpiry', 'hide_courses_categoryid')], '*', MUST_EXIST);
        $timenotified = strtotime('-' . get_config('local_courseexpiry', 'timetodeletionweeks') . ' week');

        // Now select all courses that should be deleted.
        // Prüfung auf timedelete ist eigentlich nicht notwendig?
        // wichtig ist Prüfung auf timeusernotified, damit erst nach X Wochen nach der Info Email der Kurs gelöscht wird
        $items = $DB->get_records_sql("SELECT expiry.*
            FROM {local_courseexpiry} expiry
            JOIN {course} c ON expiry.courseid = c.id
            WHERE expiry.status = 1 AND expiry.keep=0 AND expiry.timeusersnotified>0 AND expiry.timeusersnotified<? AND expiry.timedelete < ?
            ORDER BY c.id
        ", [
            $timenotified,
            time(),
        ]);

        self::output(count($items) . " courses need to be hidden, moving them to category {$hide_courses_category->id} ({$hide_courses_category->name})");

        $extra_title = 'Wird gelöscht: ';

        $item_cnt = count($items);
        $item_i = 0;
        foreach ($items as $item) {
            $item_i++;
            self::output("{$item_i}/{$item_cnt}: Hide course #$item->courseid of entry #$item->id");

            $course = get_course($item->courseid);
            $rebuild_cache = false;
            $update_course = false;

            // kein verschieben mehr, weil das zu lange dauert
            // auf eduvidual bei 60k Kurse dauert das 72 Stunden!
            /*
            if ($course->category != $hide_courses_category->id) {
                self::output('move course to backup category');

                // remember old category
                $DB->set_field('local_courseexpiry', 'original_categoryid', $course->category, ['id' => $item->id]);

                $course->category = $hide_courses_category->id;
                try {
                    update_course($course);
                } catch (\moodle_exception $e) {
                    self::output('Error: ' . $e->getMessage());
                    continue;
                }

                $rebuild_cache = true;
            }
            */

            if (!str_starts_with($course->fullname, $extra_title)) {
                $course->fullname = $extra_title . $course->fullname;
                $update_course = true;
            }

            if ($course->visible) {
                // hide the course
                $course->visible = false;
                $update_course = true;
            }

            if ($update_course) {
                self::output('update course');
                $DB->update_record('course', $course);

                echo 'New course name: ' . $course->fullname."\n";

                $rebuild_cache = true;
            } else {
                echo 'course name: ' . $course->fullname . "\n";
            }

            if ($rebuild_cache) {
                self::output('rebuild cache');
                rebuild_course_cache($course->id);
            }
        }
    }

    public static function delete_courses() {
        global $CFG, $DB;

        // Now select all courses that should be deleted.
        $items = $DB->get_records_sql("SELECT expiry.*
            FROM {local_courseexpiry} expiry
            JOIN {course} c ON expiry.courseid = c.id
            WHERE expiry.status = 1 AND expiry.keep=0 AND expiry.timedelete < ?", [time()]);

        self::output(count($items) . " courses need to be deleted");

        $hide_courses_category = $DB->get_record('course_categories', ['id' => get_config('local_courseexpiry', 'hide_courses_categoryid')], '*', MUST_EXIST);

        $backupdir = get_config('local_courseexpiry', 'backupdir');
        if (!$backupdir) {
            throw new \moodle_exception('config/backupdir not set', 'local_courseexpiry');
        }

        foreach ($items as $item) {
            self::output("Remove course #$item->courseid of entry #$item->id");

            $course = get_course($item->courseid);
            if ($course->visible) {
                self::output("Course #$item->courseid is still visible, skipping deletion.");
                continue;
            }
            if ($course->category != $hide_courses_category->id) {
                self::output("Course #$item->courseid is not in the hidden category, skipping deletion.");
                continue;
            }

            // use the original course categoryid
            $course->category = $item->original_categoryid;

            static::backup_course($course, $backupdir);

            if (!empty($CFG->developermode)) {
                self::output("Moodle is in developer mode, skipping deletion.");
                continue;
            }

            \delete_course($item->courseid, false);
            $DB->delete_records('local_courseexpiry', array('courseid' => $item->courseid));
        }
    }

    public static function get_expired_time(): int {
        $time = strtotime(get_config('local_courseexpiry', 'expire_time'));
        if (!$time) {
            throw new \moodle_exception('config/expire_time not set or wrong format', 'local_courseexpiry');
        }

        return $time;
    }

    /**
     * Return all courses of a user that are expired.
     */
    public static function get_expired_courses_teacher(): array {
        global $DB, $USER;
        $usercourses = \enrol_get_all_users_courses($USER->id, true);

        if (!$usercourses) {
            return [];
        }

        $editingcourseids = array();
        foreach ($usercourses as $usercourse) {
            $ctx = \context_course::instance($usercourse->id);
            if (has_capability('moodle/course:update', $ctx, $USER, false)) {
                $editingcourseids[] = $usercourse->id;
            }
        }

        if (!$editingcourseids) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($editingcourseids);
        $sql = "SELECT c.id, ce.timedelete
                FROM {course} c
                JOIN {local_courseexpiry} ce ON c.id = ce.courseid
                WHERE ce.status=1
                    AND c.id $insql";
        $courses = $DB->get_records_sql($sql, $inparams);

        return $courses;
    }

    /**
     * Get the timestamp when the last course will be deleted.
     * @param courses list of courses retrieved by self::get_expired_courses()
     */
    public static function get_lasttimedelete($courses) {
        $lasttimedelete = 0;
        $courses = self::get_expired_courses_teacher();

        foreach ($courses as $course) {
            if ($lasttimedelete < $course->timedelete) {
                $lasttimedelete = $course->timedelete;
            }
        }
        return $lasttimedelete;
    }

    private static function output(string $text) {
        if (static::$is_task) {
            mtrace($text);
        } else {
            echo "$text<br />";
        }
    }

    /**
     * Notifies all editingteachers about upcoming deletions.
     */
    public static function notify_users() {
        global $CFG, $DB;
        static::output("Notify users");

        $timetodeletionweeks = get_config('local_courseexpiry', 'timetodeletionweeks');
        $courses = $DB->get_records('local_courseexpiry', array('status' => 1, 'keep' => 0, 'timeusersnotified' => 0));
        $fromuser = \core_user::get_support_user();
        $notified = array(); // keep notified users, we only notify each user once.
        $stringman = get_string_manager();
        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->courseid);
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
                    static::output("=> Sent notification to user $user->fullname");
                }
            }

            $DB->update_record('local_courseexpiry', array('id' => $course->id, 'timeusersnotified' => time()));
        }
    }
}
