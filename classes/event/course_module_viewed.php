<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\event;

defined('MOODLE_INTERNAL') || die();

final class course_module_viewed extends \core\event\course_module_viewed {
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'interactivevideo';
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'interactivevideo', 'restore' => 'interactivevideo'];
    }
}
