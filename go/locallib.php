<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Absolute URL for a short name.
 *
 * @param string $shortname
 * @return string
 */
function local_go_make_url_from_shortname($shortname) {
    global $CFG;
    return "$CFG->wwwroot/local/go/?to=$shortname";
}

/**
 * Path to the public redirects snapshot file.
 *
 * @return string
 */
function local_go_snapshot_path() {
    global $CFG;
    return $CFG->dataroot . '/local_go/public_redirects.json';
}

/**
 * Remove the snapshot so requests fall back to the full Moodle path.
 */
function local_go_invalidate_snapshot() {
    $path = local_go_snapshot_path();
    if (file_exists($path)) {
        @unlink($path);
    }
}

/**
 * Rebuild the public redirects snapshot used by the config-only fast path.
 *
 * @return bool True when the snapshot was written successfully.
 */
function local_go_rebuild_snapshot() {
    global $CFG, $DB;

    $dir = $CFG->dataroot . '/local_go';
    check_dir_exists($dir, true, true);

    $fastredirects = get_config('local_go', 'fastredirects');
    if ($fastredirects === false) {
        // Default for sites that have not saved the setting yet.
        $fastredirects = 1;
    }

    $redirects = [];
    if (((int) get_config('local_go', 'enabled')) === 1) {
        $allowguests = ((int) get_config('local_go', 'allowguests')) === 1;
        $records = $DB->get_records('local_go', ['status' => 1], '', 'id, shortname, url, allowguest');
        foreach ($records as $record) {
            if ($allowguests || !empty($record->allowguest)) {
                $redirects[$record->shortname] = $record->url;
            }
        }
    }

    $payload = new stdClass();
    $payload->version = 1;
    // get_config returns strings; (bool)"0" is true in PHP.
    $payload->fastredirects = ((int) $fastredirects) === 1;
    // Always encode redirects as a JSON object, even when empty.
    $payload->redirects = (object) $redirects;

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        local_go_invalidate_snapshot();
        return false;
    }

    $path = local_go_snapshot_path();
    $tmp = $path . '.' . uniqid('tmp', true);
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        local_go_invalidate_snapshot();
        return false;
    }

    // Windows-safe replace: remove destination before rename.
    if (file_exists($path)) {
        @unlink($path);
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        local_go_invalidate_snapshot();
        return false;
    }

    return true;
}

/**
 * @param int $id
 * @return array
 */
function local_go_get_redirect($id) {
    global $DB;
    return (array) $DB->get_record('local_go', ['id' => $id], '*', MUST_EXIST);
}

/**
 * @param stdClass $data
 * @return int
 */
function local_go_save_redirect($data) {
    global $DB, $USER;

    local_go_invalidate_snapshot();

    $record = new stdClass();
    $record->shortname = $data->shortname;
    $record->url = $data->url;
    $record->status = $data->status;
    $record->allowguest = !empty($data->allowguest) ? 1 : 0;
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
    local_go_rebuild_snapshot();
    return $record->id;
}

/**
 * @param int $id
 * @param bool $refreshsnapshot Rebuild snapshot after delete (false for bulk).
 */
function local_go_delete_redirect($id, $refreshsnapshot = true) {
    global $DB;

    if ($refreshsnapshot) {
        local_go_invalidate_snapshot();
    }

    $DB->delete_records('local_go', ['id' => $id]);

    // Событие удаления.
    $event = \local_go\event\redirect_deleted::create([
        'objectid' => $id,
        'context' => context_system::instance()
    ]);
    $event->trigger();

    if ($refreshsnapshot) {
        local_go_rebuild_snapshot();
    }
}

/**
 * @param int $id
 */
function local_go_toggle_redirect($id) {
    global $DB, $USER;

    local_go_invalidate_snapshot();

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
    local_go_rebuild_snapshot();
}

/**
 * @param int $id
 * @return int
 */
function local_go_clone_redirect($id) {
    global $DB, $USER;

    local_go_invalidate_snapshot();

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

    local_go_rebuild_snapshot();
    return $newid;
}

function local_go_process_bulk_actions() {
    global $DB, $USER;

    if ($ids = optional_param_array('ids', [], PARAM_INT)) {
        $action = required_param('bulkaction', PARAM_ALPHA);
        list($idsql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);

        local_go_invalidate_snapshot();

        switch ($action) {
            case 'enable':
                $DB->set_field_select('local_go', 'status', 1, "id $idsql", $params);
                break;

            case 'disable':
                $DB->set_field_select('local_go', 'status', 0, "id $idsql", $params);
                break;

            case 'delete':
                foreach ($ids as $id) {
                    local_go_delete_redirect($id, false);
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

        local_go_rebuild_snapshot();
    }
}
