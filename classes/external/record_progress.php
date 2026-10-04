<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

final class record_progress extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'start' => new external_value(PARAM_INT, 'Observed playback interval start'),
            'end' => new external_value(PARAM_INT, 'Observed playback interval end'),
            'duration' => new external_value(PARAM_INT, 'Video duration'),
        ]);
    }

    public static function execute(int $cmid, int $start, int $end, int $duration): array {
        global $DB, $USER;
        ['cmid' => $cmid, 'start' => $start, 'end' => $end, 'duration' => $duration] = self::validate_parameters(
            self::execute_parameters(), compact('cmid', 'start', 'end', 'duration'));
        $cm = get_coursemodule_from_id('interactivevideo', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/interactivevideo:view', $context);
        $progress = \mod_interactivevideo\local\progress::record_segment($cm->instance, $USER->id, $start, $end, $duration);
        return [
            'percent' => (int)$progress->percent,
            'watchedseconds' => (int)$progress->watchedseconds,
            'completed' => (bool)$progress->completed,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_INT, 'Watched percentage'),
            'watchedseconds' => new external_value(PARAM_INT, 'Unique watched seconds'),
            'completed' => new external_value(PARAM_BOOL, 'Whether all configured rules are complete'),
        ]);
    }
}
