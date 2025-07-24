<?php
// local/go/classes/event/redirect_deleted.php
namespace local_go\event;

defined('MOODLE_INTERNAL') || die();

class redirect_deleted extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_go';
    }

    public static function get_name() {
        return get_string('eventredirectdeleted', 'local_go');
    }

    public function get_description() {
        return "Пользователь с id {$this->userid} удалил перенаправление с id {$this->objectid}.";
    }
}
