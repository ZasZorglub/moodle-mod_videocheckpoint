<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\completion;

use core_completion\activity_custom_completion;

final class custom_completion extends activity_custom_completion {
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $activity = $DB->get_record('interactivevideo', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $progress = $DB->get_record('interactivevideo_progress', [
            'interactivevideoid' => $activity->id,
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
            // Kept for activities created with the pre-0.1.1 form. The old
            // field name was accidentally persisted as a completion rule.
            'completionpercent' => (int)$progress->percent >= (int)$activity->completionpercent
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE,
        };
    }

    public static function get_defined_custom_rules(): array {
        return ['completionwatch', 'completionquestions', 'completionpercent'];
    }

    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('interactivevideo', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionwatch' => get_string('completiondetail:watch', 'interactivevideo', $activity->completionpercent),
            'completionquestions' => get_string('completiondetail:questions', 'interactivevideo'),
            'completionpercent' => get_string('completiondetail:watch', 'interactivevideo', $activity->completionpercent),
        ];
    }

    public function get_sort_order(): array {
        return ['completionview', 'completionwatch', 'completionquestions', 'completionpercent'];
    }
}
