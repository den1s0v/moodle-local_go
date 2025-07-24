<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/go/locallib.php');
require_once($CFG->dirroot.'/local/go/classes/redirects_table.php');
require_once($CFG->dirroot.'/local/go/edit_form.php');

admin_externalpage_setup('local_go_manage');

$context = context_system::instance();
require_capability('local/go:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$bulkaction = optional_param('bulkaction', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/go/manage.php'));
$PAGE->set_title(get_string('manage', 'local_go'));
$PAGE->set_heading(get_string('manage', 'local_go'));

// Обработка массовых действий.
if ($bulkaction && confirm_sesskey()) {
    local_go_process_bulk_actions();
    redirect($PAGE->url);
}

// Обработка отдельных действий.
if ($action) {
    switch ($action) {
        case 'add':
            $form = new local_go_edit_form(new moodle_url($PAGE->url, ['action' => 'add']));
            if ($form->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $form->get_data()) {
                local_go_save_redirect($data);
                redirect($PAGE->url, get_string('redirectsaved', 'local_go'));
            } else {
                echo $OUTPUT->header();
                $form->display();
                echo $OUTPUT->footer();
                die;
            }
            break;
            
        case 'edit':
            $redirect = local_go_get_redirect($id);
            $form = new local_go_edit_form(new moodle_url($PAGE->url, ['action' => 'edit']), $redirect);
            if ($form->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($data = $form->get_data()) {
                local_go_save_redirect($data);
                redirect($PAGE->url, get_string('redirectsaved', 'local_go'));
            } else {
                echo $OUTPUT->header();
                $form->display();
                echo $OUTPUT->footer();
                die;
            }
            break;
            
        case 'delete':
            if (confirm_sesskey() && $confirm && $id) {
                local_go_delete_redirect($id);
                redirect($PAGE->url, get_string('redirectdeleted', 'local_go'));
            } else {
                echo $OUTPUT->header();
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
            if (confirm_sesskey() && $id) {
                local_go_toggle_redirect($id);
                redirect($PAGE->url);
            }
            break;
            
        case 'clone':
            if (confirm_sesskey() && $id) {
                local_go_clone_redirect($id);
                redirect($PAGE->url, get_string('redirectcloned', 'local_go'));
            }
            break;
    }
}

// Отображение основного интерфейса.
echo $OUTPUT->header();

// Кнопка добавления.
echo html_writer::link(
    new moodle_url($PAGE->url, ['action' => 'add']),
    get_string('addnewredirect', 'local_go'),
    ['class' => 'btn btn-primary mb-3']
);

// Отображение таблицы.
$table = new local_go_redirects_table('local-go-redirects');
$table->define_baseurl($PAGE->url);
$table->out(25, true);

echo $OUTPUT->footer();
