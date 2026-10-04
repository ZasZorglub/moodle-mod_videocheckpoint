<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

class restore_interactivevideo_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure(): array {
        return [
            new restore_path_element('interactivevideo', '/activity/interactivevideo'),
            new restore_path_element('interactivevideo_cue', '/activity/interactivevideo/cues/cue'),
        ];
    }

    protected function process_interactivevideo(array $data): void {
        global $DB;
        $record = (object)$data;
        $record->course = $this->get_courseid();
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('interactivevideo', $record);
        $this->apply_activity_instance($newid);
    }

    protected function process_interactivevideo_cue(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->interactivevideoid = $this->get_new_parentid('interactivevideo');
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('interactivevideo_cues', $record);
        $this->set_mapping('interactivevideo_cue', $oldid, $newid);
    }

    protected function after_execute(): void {
        $this->add_related_files('mod_interactivevideo', 'intro', null);
        $this->add_related_files('mod_interactivevideo', 'video', 0);
    }
}
