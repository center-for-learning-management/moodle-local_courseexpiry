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

$tasks = array(
    array(
        'classname' => 'local_courseexpiry\task\task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '7',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),
    array(
        'classname' => 'local_courseexpiry\task\delete_courses',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '7',
        'day' => '0',
        'dayofweek' => '0',
        'month' => '0',
        'disabled' => 1, // This task is disabled by default
    ),
);
