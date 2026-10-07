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
 * AJAX endpoint for submitting checkpoint answers.
 */
final class submit_answer extends external_api {
    /**
     * {@inheritdoc}
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'cueid' => new external_value(PARAM_INT, 'Checkpoint ID'),
            'answer' => new external_value(PARAM_INT, 'Zero-based option number'),
        ]);
    }

    /**
     * Validates and stores a checkpoint answer.
     *
     * @param int $cmid Course module ID.
     * @param int $cueid Checkpoint ID.
     * @param int $answer Zero-based answer index.
     * @return array Answer result and feedback.
     */
    public static function execute(int $cmid, int $cueid, int $answer): array {
        global $DB, $USER;
        ['cmid' => $cmid, 'cueid' => $cueid, 'answer' => $answer] = self::validate_parameters(
            self::execute_parameters(),
            compact('cmid', 'cueid', 'answer')
        );
        $cm = get_coursemodule_from_id('videocheckpoint', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videocheckpoint:view', $context);
        $cue = $DB->get_record('videocheckpoint_cues', [
            'id' => $cueid,
            'videocheckpointid' => $cm->instance,
            'status' => 'published',
        ], '*', MUST_EXIST);
        $options = json_decode($cue->optionsjson, true) ?: [];
        if (!array_key_exists($answer, $options)) {
            throw new \invalid_parameter_exception('The selected option does not exist.');
        }
        $correct = $answer === (int)$cue->correctanswer;
        $DB->insert_record('videocheckpoint_answers', (object) [
            'cueid' => $cue->id,
            'userid' => $USER->id,
            'answer' => $answer,
            'correct' => (int)$correct,
            'timecreated' => time(),
        ]);
        $activity = $DB->get_record('videocheckpoint', ['id' => $cm->instance], '*', MUST_EXIST);
        \mod_videocheckpoint\local\progress::recalculate($activity, $USER->id);
        return [
            'correct' => $correct,
            'feedback' => (string)$cue->feedback,
            'required' => (bool)$cue->required,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'correct' => new external_value(PARAM_BOOL, 'Whether the answer is correct'),
            'feedback' => new external_value(PARAM_TEXT, 'Teacher feedback'),
            'required' => new external_value(PARAM_BOOL, 'Whether a correct answer is required'),
        ]);
    }
}
