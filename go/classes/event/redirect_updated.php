<?php
// local/go/classes/event/redirect_updated.php
namespace local_go\event;

defined('MOODLE_INTERNAL') || die();

class redirect_updated extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_go';
    }

    public static function get_name() {
        return get_string('eventredirectupdated', 'local_go');
    }

    public function get_description() {
        return "Пользователь с id {$this->userid} обновил перенаправление с id {$this->objectid}.";
    }
}
