<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

function xmldb_local_courseexpiry_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025080600) {
        // Define field timeusersnotified to be added to local_courseexpiry.
        $table = new xmldb_table('local_courseexpiry');
        $field = new xmldb_field('timeusersnotified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field timeusersnotified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Courseexpiry savepoint reached.
        upgrade_plugin_savepoint(true, 2025080600, 'local', 'courseexpiry');
    }

    if ($oldversion < 2025080701) {
        // Define field keep to be added to local_courseexpiry.
        $table = new xmldb_table('local_courseexpiry');
        $field = new xmldb_field('keep', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field keep.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field original_categoryid to be added to local_courseexpiry.
        $table = new xmldb_table('local_courseexpiry');
        $field = new xmldb_field('original_categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timedelete');

        // Conditionally launch add field original_categoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Courseexpiry savepoint reached.
        upgrade_plugin_savepoint(true, 2025080701, 'local', 'courseexpiry');
    }

    return true;
}
