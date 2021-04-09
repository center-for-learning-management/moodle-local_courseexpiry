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

require_once($CFG->libdir . "/externallib.php");

class local_courseexpiry_external extends external_api {
    public static function toggle_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'the course id'),
            'status' => new external_value(PARAM_INT, '1 or 0'),
        ));
    }

    /**
     * Toggle status.
     */
    public static function toggle($courseid, $status) {
        global $DB, $USER;
        $params = self::validate_parameters(self::toggle_parameters(), array('courseid' => $courseid, 'status' => $status));
        $ret = array(
            'courseid' => 0,
            'status' => 0,
        );

        $ctx = \context_course::instance($params['courseid']);
        if (in_array($params['status'], array(0,1)) && has_capability('moodle/course:update', $ctx, $USER, false)) {
            $DB->set_field('local_courseexpiry', 'status', $params['status'], array('courseid' => $params['courseid']));
            $ret['courseid'] = $params['courseid'];
            $ret['status'] = $params['status'];
        }

        return $ret;
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function toggle_returns() {
        return new external_single_structure(array(
            'courseid' => new external_value(PARAM_INT, 'courseid or 0 if failed'),
            'status' => new external_value(PARAM_INT, 'current status'),
        ));
    }
}
