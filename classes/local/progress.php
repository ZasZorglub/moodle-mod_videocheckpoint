<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\local;

final class progress {
    public static function record_segment(int $activityid, int $userid, int $start, int $end, int $duration): \stdClass {
        global $DB;
        $activity = $DB->get_record('interactivevideo', ['id' => $activityid], '*', MUST_EXIST);
        $duration = max(0, $duration);
        $start = max(0, min($start, $duration));
        $end = max($start, min($end, $duration));
        if ($end > $start && ($end - $start) <= 30) {
            $DB->insert_record('interactivevideo_segments', (object) [
                'interactivevideoid' => $activityid,
                'userid' => $userid,
                'starttime' => $start,
                'endtime' => $end,
                'timecreated' => time(),
            ]);
        }
        return self::recalculate($activity, $userid, $duration);
    }

    public static function recalculate(\stdClass $activity, int $userid, int $duration = 0): \stdClass {
        global $DB;
        $segments = $DB->get_records('interactivevideo_segments', [
            'interactivevideoid' => $activity->id,
            'userid' => $userid,
        ], 'starttime ASC, endtime ASC', 'id,starttime,endtime');
        $watched = self::union_seconds(array_map(static fn($row) => [
            (int)$row->starttime,
            (int)$row->endtime,
        ], $segments));

        $existing = $DB->get_record('interactivevideo_progress', [
            'interactivevideoid' => $activity->id,
            'userid' => $userid,
        ]);
        $duration = max($duration, (int)($existing->duration ?? 0));
        $percent = $duration > 0 ? min(100, (int)floor(($watched / $duration) * 100)) : 0;
        $required = $DB->get_records('interactivevideo_cues', [
            'interactivevideoid' => $activity->id,
            'required' => 1,
            'status' => 'published',
        ], '', 'id');
        $answered = 0;
        foreach ($required as $cue) {
            if ($DB->record_exists('interactivevideo_answers', [
                'cueid' => $cue->id,
                'userid' => $userid,
                'correct' => 1,
            ])) {
                $answered++;
            }
        }
        $questionscomplete = count($required) === 0 || $answered === count($required);
        $watchcomplete = empty($activity->completionwatch) || $percent >= (int)$activity->completionpercent;
        $allquestionscomplete = empty($activity->completionquestions) || $questionscomplete;

        $record = (object) [
            'interactivevideoid' => $activity->id,
            'userid' => $userid,
            'duration' => $duration,
            'watchedseconds' => $watched,
            'percent' => $percent,
            'questionscomplete' => (int)$questionscomplete,
            'completed' => (int)($watchcomplete && $allquestionscomplete),
            'grade' => count($required) > 0 ? ($answered / count($required)) * (int)$activity->grade : null,
            'timemodified' => time(),
        ];
        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('interactivevideo_progress', $record);
        } else {
            $record->timecreated = time();
            $record->id = $DB->insert_record('interactivevideo_progress', $record);
        }
        if ($record->grade !== null) {
            interactivevideo_grade_item_update($activity, [(object) [
                'userid' => $userid,
                'rawgrade' => $record->grade,
            ]]);
        }
        self::update_completion($activity, $userid);
        return $record;
    }

    public static function union_seconds(array $segments): int {
        if (!$segments) {
            return 0;
        }
        usort($segments, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        [$start, $end] = $segments[0];
        $total = 0;
        foreach (array_slice($segments, 1) as [$nextstart, $nextend]) {
            if ($nextstart <= $end + 1) {
                $end = max($end, $nextend);
                continue;
            }
            $total += max(0, $end - $start);
            [$start, $end] = [$nextstart, $nextend];
        }
        return $total + max(0, $end - $start);
    }

    private static function update_completion(\stdClass $activity, int $userid): void {
        $cm = get_coursemodule_from_instance('interactivevideo', $activity->id, $activity->course, false, MUST_EXIST);
        $course = get_course($activity->course);
        $completion = new \completion_info($course);
        $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
    }
}
