<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/go/locallib.php');

admin_externalpage_setup('local_go_manage');

$context = context_system::instance();
require_capability('local/go:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url('/local/go/index.php'));
$PAGE->set_title(get_string('manage', 'local_go'));
$PAGE->set_heading(get_string('manage', 'local_go'));

echo $OUTPUT->header();

// Обработка действий
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'delete':
            if ($confirm && $id) {
                local_go_delete_redirect($id);
                redirect($PAGE->url, get_string('redirectdeleted', 'local_go'));
            } else {
                echo $OUTPUT->confirm(
                    get_string('confirmdelete', 'local_go'),
                    new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $id, 'confirm' => 1]),
                    $PAGE->url
                );
                echo $OUTPUT->footer();
                die;
            }
            break;
        case 'toggle':
            if ($id) {
                local_go_toggle_redirect($id);
                redirect($PAGE->url);
            }
            break;
        case 'clone':
            if ($id) {
                local_go_clone_redirect($id);
                redirect($PAGE->url, get_string('redirectcloned', 'local_go'));
            }
            break;
    }
}

// Отображение списка
$table = new local_go_redirects_table('local-go-redirects');
$table->define_baseurl($PAGE->url);
$table->out(25, true);

echo $OUTPUT->footer();
