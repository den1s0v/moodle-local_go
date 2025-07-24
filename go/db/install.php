<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_go_install() {
    // Добавляем пункт в навигацию
    core_plugin_manager::reset_caches();
}
