<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_go_install() {
    global $CFG;

    // Default: fast public redirects enabled.
    set_config('fastredirects', 1, 'local_go');

    require_once($CFG->dirroot . '/local/go/locallib.php');
    local_go_rebuild_snapshot();

    core_plugin_manager::reset_caches();
}
