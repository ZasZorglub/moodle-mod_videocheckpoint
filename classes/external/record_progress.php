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

namespace mod_videocheckpoint\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * AJAX endpoint for recording watched video intervals.
 */
final class record_progress extends external_api {
    /**
     * {@inheritdoc}
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'start' => new external_value(PARAM_INT, 'Observed playback interval start'),
            'end' => new external_value(PARAM_INT, 'Observed playback interval end'),
            'duration' => new external_value(PARAM_INT, 'Video duration'),
        ]);
    }

    /**
     * Records a watched interval.
     *
     * @param int $cmid Course module ID.
     * @param int $start Interval start in seconds.
     * @param int $end Interval end in seconds.
     * @param int $duration Video duration in seconds.
     * @return array Updated progress summary.
     */
    public static function execute(int $cmid, int $start, int $end, int $duration): array {
        global $USER;
        ['cmid' => $cmid, 'start' => $start, 'end' => $end, 'duration' => $duration] = self::validate_parameters(
            self::execute_parameters(),
            compact('cmid', 'start', 'end', 'duration')
        );
        $cm = get_coursemodule_from_id('videocheckpoint', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videocheckpoint:view', $context);
        $progress = \mod_videocheckpoint\local\progress::record_segment($cm->instance, $USER->id, $start, $end, $duration);
        return [
            'percent' => (int)$progress->percent,
            'watchedseconds' => (int)$progress->watchedseconds,
            'completed' => (bool)$progress->completed,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_INT, 'Watched percentage'),
            'watchedseconds' => new external_value(PARAM_INT, 'Unique watched seconds'),
            'completed' => new external_value(PARAM_BOOL, 'Whether all configured rules are complete'),
        ]);
    }
}
