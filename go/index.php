<?php
require_once('../../config.php');
require_login();

// Get and sanitize shortname.
$shortname = optional_param('to', '', PARAM_ALPHANUMEXT);
if (empty($shortname)) {
    throw new moodle_exception('missingparam', 'error', '', 'to');
}

global $DB;

// Fetch redirect rule.
$redirect = $DB->get_record('local_go', ['shortname' => $shortname]);
if (!$redirect) {
    header("HTTP/1.0 404 Not Found");
    echo get_string('errornotfound', 'local_go');
    die;
}

// Check status.
if (!$redirect->status) {
    header("HTTP/1.0 403 Forbidden");
    echo get_string('errordisabled', 'local_go');
    die;
}

// Perform redirect.
redirect(new moodle_url($redirect->url));
