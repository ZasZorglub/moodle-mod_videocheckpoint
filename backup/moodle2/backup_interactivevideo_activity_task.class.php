<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

class backup_interactivevideo_activity_task extends backup_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_interactivevideo_activity_structure_step('interactivevideo_structure', 'interactivevideo.xml'));
    }

    public static function encode_content_links($content): string {
        return $content;
    }
}
