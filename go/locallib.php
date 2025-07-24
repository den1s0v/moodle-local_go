<?php
defined('MOODLE_INTERNAL') || die();

function local_go_delete_redirect($id) {
    global $DB, $USER;
    
    $DB->delete_records('local_go', ['id' => $id]);
    
    // Trigger event.
    $event = \local_go\event\redirect_deleted::create([
        'objectid' => $id,
        'userid' => $USER->id,
        'context' => context_system::instance()
    ]);
    $event->trigger();
}

function local_go_toggle_redirect($id) {
    global $DB, $USER;
    
    $redirect = $DB->get_record('local_go', ['id' => $id], '*', MUST_EXIST);
    $redirect->status = !$redirect->status;
    $redirect->timemodified = time();
    $redirect->userid = $USER->id;
    
    $DB->update_record('local_go', $redirect);
    
    // Trigger event.
    $event = \local_go\event\redirect_updated::create([
        'objectid' => $id,
        'userid' => $USER->id,
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
    
    // Trigger event.
    $event = \local_go\event\redirect_created::create([
        'objectid' => $newid,
        'userid' => $USER->id,
        'context' => context_system::instance()
    ]);
    $event->trigger();
    
    return $newid;
}

function local_go_process_bulk_actions() {
    global $DB, $USER;
    
    if ($ids = optional_param_array('ids', [], PARAM_INT)) {
        $action = required_param('bulkaction', PARAM_ALPHA);
        
        switch ($action) {
            case 'enable':
                $DB->set_field_select('local_go', 'status', 1, 'id IN ('.implode(',', $ids).')');
                break;
            case 'disable':
                $DB->set_field_select('local_go', 'status', 0, 'id IN ('.implode(',', $ids).')');
                break;
            case 'delete':
                foreach ($ids as $id) {
                    local_go_delete_redirect($id);
                }
                break;
        }
        
        // Update timestamps.
        $DB->execute("UPDATE {local_go} SET timemodified = ?, userid = ? WHERE id IN (".implode(',', $ids).")", 
            [time(), $USER->id]);
    }
}
