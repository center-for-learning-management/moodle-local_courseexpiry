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

namespace local_courseexpiry\task;

defined('MOODLE_INTERNAL') || die;

class local_courseexpiry_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('task', 'local_courseexpiry');
    }

    public function execute() {
        global $DB;

        $sql = "SELECT id
                    FROM {course}
                    WHERE id NOT IN (
                        SELECT courseid
                            FROM {local_courseexpiry}
                    )";
        $newcourses = $DB->get_records_sql($sql, array());
        foreach ($newcourses as $newcourse) {
            $DB->insert_record('local_courseexpiry', array(
                'courseid' => $newcourse->id,
                'status' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'timedelete' => 0,
            ));
        }

        $checkstops = explode("\n", get_config('local_courseexpiry', 'checkstops'));
        $mmdd = date("md");
        if (in_array($mmdd, $checkstops)) {
            $sql = "UPDATE {local_courseexpiry}
                        SET status = 1, timemodified = ?, timedelete = ?
                        WHERE courseid IN (
                            SELECT id
                                FROM {course}
                                WHERE enddate < ?
                        )";
            $params = array(
                time(),
                strtotime('+' . get_config('local_courseexpiry', 'timetodeletionweeks') . ' week'),
                time(),
            );
            $DB->execute($sql, $params);
            \local_courseexpiry\locallib::notify_users();
        }

        $sql = "SELECT *
                    FROM {local_courseexpiry}
                    WHERE status = ? AND timedelete < ?";
        $params = array(
            1,
            time()
        );
        $deletecourses = $DB->get_records_sql($sql, $params);
        foreach ($deletecourses as $deletecourse) {
            // @todo implement real deletion once everything else works.
            echo "I would like to remove course #$deletecourse->courseid<br />";
        }
    }
}
