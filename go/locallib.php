<?php
defined('MOODLE_INTERNAL') || die();

function local_go_make_url_from_shortname($shortname) {
    global $CFG;
    return "$CFG->wwwroot/local/go/?to=$shortname";
}

function local_go_get_redirect($id) {
    global $DB;
    return (array) $DB->get_record('local_go', ['id' => $id], '*', MUST_EXIST);
}

function local_go_save_redirect($data) {
    global $DB, $USER;
    
    $record = new stdClass();
    $record->shortname = $data->shortname;
    $record->url = $data->url;
    $record->status = $data->status;
    $record->category = $data->category;
    $record->comment = $data->comment;
    $record->timemodified = time();
    $record->userid = $USER->id;
    
    if (!empty($data->id)) {
        // Обновление: сохраняем предыдущий URL.
        $old = $DB->get_record('local_go', ['id' => $data->id], 'url', MUST_EXIST);
        $record->id = $data->id;
        $record->backupurl = $old->url;
        $DB->update_record('local_go', $record);
        
        // Событие обновления.
        $event = \local_go\event\redirect_updated::create([
            'objectid' => $data->id,
            'context' => context_system::instance()
        ]);
    } else {
        // Создание новой записи.
        $record->timecreated = time();
        $record->id = $DB->insert_record('local_go', $record);
        
        // Событие создания.
        $event = \local_go\event\redirect_created::create([
            'objectid' => $record->id,
            'context' => context_system::instance()
        ]);
    }
    
    $event->trigger();
    return $record->id;
}

function local_go_delete_redirect($id) {
    global $DB;
    $DB->delete_records('local_go', ['id' => $id]);
    
    // Событие удаления.
    $event = \local_go\event\redirect_deleted::create([
        'objectid' => $id,
        'context' => context_system::instance()
    ]);
    $event->trigger();
}

function local_go_toggle_redirect($id) {
    global $DB, $USER;
    
    $redirect = $DB->get_record('local_go', ['id' => $id], '*', MUST_EXIST);
    $redirect->status = !$redirect->status;  // Flip flag.
    $redirect->timemodified = time();
    $redirect->userid = $USER->id;
    
    $DB->update_record('local_go', $redirect);
    
    // Событие обновления.
    $event = \local_go\event\redirect_updated::create([
        'objectid' => $id,
        'context' => context_system::instance(),
        'other' => ['status' => $redirect->status]
    ]);
    $event->trigger();
}

function local_go_clone_redirect($id) {
    global $DB, $USER;
    
    $redirect = $DB->get_record('local_go', ['id' => $id], '*', MUST_EXIST);
    unset($redirect->id);
    $redirect->shortname .= '_copy';
    $redirect->timemodified = time();
    $redirect->timecreated = time();
    $redirect->userid = $USER->id;
    
    $newid = $DB->insert_record('local_go', $redirect);
    
    // Событие создания.
    $event = \local_go\event\redirect_created::create([
        'objectid' => $newid,
        'context' => context_system::instance()
    ]);
    $event->trigger();
    
    return $newid;
}

function local_go_process_bulk_actions() {
    global $DB, $USER;
    
    if ($ids = optional_param_array('ids', [], PARAM_INT)) {
        $action = required_param('bulkaction', PARAM_ALPHA);
        list($idsql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        
        switch ($action) {
            case 'enable':
                $DB->set_field_select('local_go', 'status', 1, "id $idsql", $params);
                break;
                
            case 'disable':
                $DB->set_field_select('local_go', 'status', 0, "id $idsql", $params);
                break;
                
            case 'delete':
                foreach ($ids as $id) {
                    local_go_delete_redirect($id);
                }
                break;
        }
        
        // Обновление времени изменения и пользователя.
        if ($action !== 'delete') {
            $DB->execute("UPDATE {local_go} 
                          SET timemodified = :now, userid = :userid 
                          WHERE id $idsql", 
                ['now' => time(), 'userid' => $USER->id] + $params);
        }
    }
}
