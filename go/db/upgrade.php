<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_go_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090400) {
        $table = new xmldb_table('local_go');
        $field = new xmldb_field('allowguest', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'status');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026090400, 'local', 'go');
    }

    if ($oldversion < 2026090600) {
        if (get_config('local_go', 'fastredirects') === false) {
            set_config('fastredirects', 1, 'local_go');
        }

        require_once($CFG->dirroot . '/local/go/locallib.php');
        local_go_rebuild_snapshot();

        upgrade_plugin_savepoint(true, 2026090600, 'local', 'go');
    }

    return true;
}
