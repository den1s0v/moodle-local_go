<?php
require_once('../../config.php');

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

// Проверяем доступ для гостей.
if (!get_config('local_go', 'allowguests') && !isloggedin() && !isguestuser()) {
    require_login();
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

// Выполняем перенаправление.
redirect(new moodle_url($redirect->url));
