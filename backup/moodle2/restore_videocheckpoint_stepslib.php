<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Video Checkpoint activity module.
 *
 * @package     mod_videocheckpoint
 * @copyright   2026 Simple Solutions
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores Video Checkpoint activity records and files.
 */
class restore_videocheckpoint_activity_structure_step extends restore_activity_structure_step {
    /**
     * {@inheritdoc}
     */
    protected function define_structure(): array {
        return [
            new restore_path_element('videocheckpoint', '/activity/videocheckpoint'),
            new restore_path_element('videocheckpoint_cue', '/activity/videocheckpoint/cues/cue'),
        ];
    }

    /**
     * Restores the activity record.
     *
     * @param array $data Backup data.
     */
    protected function process_videocheckpoint(array $data): void {
        global $DB;
        $record = (object)$data;
        $record->course = $this->get_courseid();
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('videocheckpoint', $record);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restores a checkpoint record.
     *
     * @param array $data Backup data.
     */
    protected function process_videocheckpoint_cue(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->videocheckpointid = $this->get_new_parentid('videocheckpoint');
        $record->timecreated = $this->apply_date_offset($record->timecreated);
        $record->timemodified = $this->apply_date_offset($record->timemodified);
        $newid = $DB->insert_record('videocheckpoint_cues', $record);
        $this->set_mapping('videocheckpoint_cue', $oldid, $newid);
    }

    /**
     * {@inheritdoc}
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videocheckpoint', 'intro', null);
        $this->add_related_files('mod_videocheckpoint', 'video', 0);
    }
}
