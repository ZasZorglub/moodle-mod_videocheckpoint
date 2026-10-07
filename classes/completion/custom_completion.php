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

namespace mod_videocheckpoint\completion;

use core_completion\activity_custom_completion;

/**
 * Evaluates Video Checkpoint custom completion rules.
 */
final class custom_completion extends activity_custom_completion {
    /**
     * {@inheritdoc}
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('videocheckpoint', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $progress = $DB->get_record('videocheckpoint_progress', [
            'videocheckpointid' => $activity->id,
            'userid' => $this->userid,
        ]);
        if (!$progress) {
            return COMPLETION_INCOMPLETE;
        }
        return match ($rule) {
            'completionwatch' => (int)$progress->percent >= (int)$activity->completionpercent
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
            'completionquestions' => !empty($progress->questionscomplete)
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
        };
    }

    /**
     * {@inheritdoc}
     */
    public static function get_defined_custom_rules(): array {
        return ['completionwatch', 'completionquestions'];
    }

    /**
     * {@inheritdoc}
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videocheckpoint', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionwatch' => get_string('completiondetail:watch', 'videocheckpoint', $activity->completionpercent),
            'completionquestions' => get_string('completiondetail:questions', 'videocheckpoint'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionwatch', 'completionquestions'];
    }
}
