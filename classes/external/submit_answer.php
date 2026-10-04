<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

final class submit_answer extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'cueid' => new external_value(PARAM_INT, 'Checkpoint ID'),
            'answer' => new external_value(PARAM_INT, 'Zero-based option number'),
        ]);
    }

    public static function execute(int $cmid, int $cueid, int $answer): array {
        global $DB, $USER;
        ['cmid' => $cmid, 'cueid' => $cueid, 'answer' => $answer] = self::validate_parameters(
            self::execute_parameters(), compact('cmid', 'cueid', 'answer'));
        $cm = get_coursemodule_from_id('interactivevideo', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/interactivevideo:view', $context);
        $cue = $DB->get_record('interactivevideo_cues', [
            'id' => $cueid,
            'interactivevideoid' => $cm->instance,
        ], '*', MUST_EXIST);
        $options = json_decode($cue->optionsjson, true) ?: [];
        if (!array_key_exists($answer, $options)) {
            throw new \invalid_parameter_exception('The selected option does not exist.');
        }
        $correct = $answer === (int)$cue->correctanswer;
        $DB->insert_record('interactivevideo_answers', (object) [
            'cueid' => $cue->id,
            'userid' => $USER->id,
            'answer' => $answer,
            'correct' => (int)$correct,
            'timecreated' => time(),
        ]);
        $activity = $DB->get_record('interactivevideo', ['id' => $cm->instance], '*', MUST_EXIST);
        \mod_interactivevideo\local\progress::recalculate($activity, $USER->id);
        return [
            'correct' => $correct,
            'feedback' => (string)$cue->feedback,
            'required' => (bool)$cue->required,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'correct' => new external_value(PARAM_BOOL, 'Whether the answer is correct'),
            'feedback' => new external_value(PARAM_TEXT, 'Teacher feedback'),
            'required' => new external_value(PARAM_BOOL, 'Whether a correct answer is required'),
        ]);
    }
}
