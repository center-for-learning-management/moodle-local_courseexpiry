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
local_courseexpiry\permissions::require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/courseexpiry/expiredcourses.php', array());
$PAGE->set_title(get_string('expired_courses', 'local_courseexpiry'));
$PAGE->set_heading(get_string('expired_courses', 'local_courseexpiry'));

$PAGE->navbar->add(get_string('expired_courses', 'local_courseexpiry'), $PAGE->url);

if (is_siteadmin()) {
    $showall = optional_param('showall', 0, PARAM_BOOL);
} else {
    $showall = false;
}

if ($showall) {
    $courses = null;
} else {
    $courses = \local_courseexpiry\locallib::get_expired_courses_teacher();
    $lasttimedelete = \local_courseexpiry\locallib::get_lasttimedelete($courses);
    \set_user_preference('block_courseexpiry_minimizeuntil', $lasttimedelete);
}

class expiredcourses_table extends \local_table_sql\table_sql {
    public function __construct(private bool $showall, private ?array $courses) {
        parent::__construct();
    }

    protected function define_table_configs() {
        global $DB;

        $sql = "
            SELECT c.id,c.fullname AS name,ce.status,ce.timedelete,ce.keep
            , keep as my_delete
            FROM {course} c
            JOIN {local_courseexpiry} ce ON c.id = ce.courseid
            WHERE ce.status=1
        ";
        $params = [];

        if (!$this->showall) {
            [$courses_sql, $courses_params] = $DB->get_in_or_equal(array_keys($this->courses), onemptyitems: null);

            $sql .= " AND c.id {$courses_sql}";
            $params = array_merge($params, $courses_params);
        }

        $this->set_sql_query($sql, $params);

        // Define headers and columns.
        $cols = [
            'my_delete' => get_string('delete', 'local_courseexpiry'),
            'keep' => get_string('keep', 'local_courseexpiry'),
            'name' => get_string('name'),
            'timedelete' => get_string('timedelete', 'local_courseexpiry'),
        ];
        $this->set_table_columns($cols);

        $this->sortable(true, 'name', SORT_ASC);

        // $this->set_column_options('timedelete', data_type: static::PARAM_TIMESTAMP);
    }

    function col_my_delete($row) {
        ob_start();
        ?>
        <div>

            <a href="#" class="expiredcourse-toggler" onclick="var el = this; require(['local_courseexpiry/main'], function(M) { M.toggle(el, <?=$row->id?>, 'delete'); }); return false;">
                <?php if (!$row->keep): ?>
                    <i class="fa fa-check-square" style="color: black;"></i>
                <?php else: ?>
                    <i class="fa fa-square" style="color: darkgray;"></i>
                <?php endif; ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    function col_keep($row) {
        ob_start();
        ?>
        <div>
            <a href="#" class="expiredcourse-toggler" onclick="var el = this; require(['local_courseexpiry/main'], function(M) { M.toggle(el, <?=$row->id?>, 'keep'); }); return false;">
                <?php if (!$row->keep): ?>
                    <i class="fa fa-square" style="color: darkgray;"></i>
                <?php else: ?>
                    <i class="fa fa-check-square" style="color: black;"></i>
                <?php endif; ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    function col_name($row) {
        return $this->format_col_content($row->name, link: new \moodle_url("/course/view.php?id={$row->id}"));
    }

    function col_timedelete($row) {
        return userdate($row->timedelete, get_string('strftimedate'));
    }
}

$table = new expiredcourses_table($showall, $courses);

echo $OUTPUT->header();

if ($showall && !empty($CFG->developermode)) {
    // only run on local dev, on production this is too slow and done by the scheduled task
    \local_courseexpiry\locallib::check_expiry();
}

if (is_siteadmin() && !$showall) {
    echo '<div class="mb-3">';
    echo '<a href="' . new moodle_url('/local/courseexpiry/expiredcourses.php', array('showall' => 1)) . '" class="btn btn-secondary">' .
        'Show all Moodle Courses' . '</a>';
    echo '</div>';
}

$table->out();

echo $OUTPUT->footer();
