<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

class restore_interactivevideo_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_interactivevideo_activity_structure_step('interactivevideo_structure', 'interactivevideo.xml'));
    }

    public static function define_decode_contents(): array {
        return [];
    }

    public static function define_decode_rules(): array {
        return [];
    }
}
