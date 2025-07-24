<?php
namespace local_go\event;

defined('MOODLE_INTERNAL') || die();

class redirect_created extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_go';
    }

    public static function get_name() {
        return get_string('eventredirectcreated', 'local_go');
    }

    public function get_description() {
        return "Пользователь с id {$this->userid} создал перенаправление с id {$this->objectid}.";
    }
}
