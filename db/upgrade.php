<?php

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
