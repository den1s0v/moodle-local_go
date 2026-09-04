<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_go_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090400) {
        $table = new xmldb_table('local_go');
        $field = new xmldb_field('allowguest', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'status');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026090400, 'local', 'go');
    }

    return true;
}
