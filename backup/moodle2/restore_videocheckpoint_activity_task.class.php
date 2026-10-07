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
 * Restore task for Video Checkpoint activities.
 */
class restore_videocheckpoint_activity_task extends restore_activity_task {
    /**
     * {@inheritdoc}
     */
    protected function define_my_settings(): void {
    }

    /**
     * {@inheritdoc}
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_videocheckpoint_activity_structure_step('videocheckpoint_structure', 'videocheckpoint.xml'));
    }

    /**
     * {@inheritdoc}
     */
    public static function define_decode_contents(): array {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function define_decode_rules(): array {
        return [];
    }
}
