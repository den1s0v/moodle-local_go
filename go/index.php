<?php
// Config-only bootstrap: без БД и сессии для быстрого публичного редиректа.
define('ABORT_AFTER_CONFIG', true);
require_once('../../config.php');

$snapshot = null;
$snapshotpath = $CFG->dataroot . '/local_go/public_redirects.json';
if (is_readable($snapshotpath)) {
    $raw = @file_get_contents($snapshotpath);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['fastredirects'], $decoded['redirects']) && is_array($decoded['redirects'])) {
            $snapshot = $decoded;
        }
    }
}

// Быстрый путь: публичная ссылка из snapshot без полного Moodle.
if ($snapshot !== null && !empty($snapshot['fastredirects'])) {
    $shortname = '';
    if (isset($_GET['to'])) {
        // PARAM_ALPHANUMEXT-совместимая очистка (config-only, optional_param недоступен).
        $shortname = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['to']);
    }
    if ($shortname !== '' && isset($snapshot['redirects'][$shortname])) {
        $url = (string) $snapshot['redirects'][$shortname];
        if ($url !== '' && $url[0] === '/') {
            $url = rtrim($CFG->wwwroot, '/') . $url;
        }
        if ($url !== '' && preg_match('#^(/|https?://)#i', $url)) {
            header('Cache-Control: no-store');
            header('Location: ' . $url, true, 302);
            exit;
        }
    }
}

// Полный bootstrap Moodle (приватные ссылки, miss, выключенная оптимизация).
define('ABORT_AFTER_CONFIG_CANCEL', true);
require("$CFG->dirroot/lib/setup.php");

require_once($CFG->dirroot . '/local/go/locallib.php');

// Самовосстановление snapshot после обновления или повреждения.
if ($snapshot === null) {
    local_go_rebuild_snapshot();
}

// Проверяем глобальное включение плагина.
if (!get_config('local_go', 'enabled')) {
    header("HTTP/1.0 404 Not Found");
    die;
}

// Получаем и проверяем короткое имя.
$shortname = optional_param('to', '', PARAM_ALPHANUMEXT);
if (empty($shortname)) {
    throw new moodle_exception('missingparam', 'error', '', 'to');
}

global $DB;

// Ищем правило перенаправления.
$redirect = $DB->get_record('local_go', ['shortname' => $shortname]);
if (!$redirect) {
    header("HTTP/1.0 404 Not Found");
    echo get_string('errornotfound', 'local_go');
    die;
}

// Проверяем статус правила.
if (!$redirect->status) {
    header("HTTP/1.0 403 Forbidden");
    echo get_string('errordisabled', 'local_go');
    die;
}

// Публичные ссылки (флаг правила или глобальная настройка) работают без входа.
$public = !empty($redirect->allowguest) || get_config('local_go', 'allowguests');
if (!$public && !isloggedin()) {
    require_login();
}

// Выполняем перенаправление.
redirect(new moodle_url($redirect->url));
