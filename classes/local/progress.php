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

namespace mod_videocheckpoint\local;

/**
 * Stores watched intervals and calculates learner progress.
 */
final class progress {
    /**
     * Stores a validated playback interval and recalculates progress.
     *
     * @param int $activityid Activity instance ID.
     * @param int $userid User ID.
     * @param int $start Interval start in seconds.
     * @param int $end Interval end in seconds.
     * @param int $duration Video duration in seconds.
     * @return \stdClass Updated progress record.
     */
    public static function record_segment(int $activityid, int $userid, int $start, int $end, int $duration): \stdClass {
        global $DB;
        $activity = $DB->get_record('videocheckpoint', ['id' => $activityid], '*', MUST_EXIST);
        $duration = max(0, $duration);
        $start = max(0, min($start, $duration));
        $end = max($start, min($end, $duration));
        if ($end > $start && ($end - $start) <= 30) {
            $DB->insert_record('videocheckpoint_segments', (object) [
                'videocheckpointid' => $activityid,
                'userid' => $userid,
                'starttime' => $start,
                'endtime' => $end,
                'timecreated' => time(),
            ]);
        }
        return self::recalculate($activity, $userid, $duration);
    }

    /**
     * Recalculates stored progress, completion and grade.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid User ID.
     * @param int $duration Optional video duration in seconds.
     * @return \stdClass Updated progress record.
     */
    public static function recalculate(\stdClass $activity, int $userid, int $duration = 0): \stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/videocheckpoint/lib.php');
        $segments = $DB->get_records('videocheckpoint_segments', [
            'videocheckpointid' => $activity->id,
            'userid' => $userid,
        ], 'starttime ASC, endtime ASC', 'id,starttime,endtime');
        $watched = self::union_seconds(array_map(static fn($row) => [
            (int)$row->starttime,
            (int)$row->endtime,
        ], $segments));

        $existing = $DB->get_record('videocheckpoint_progress', [
            'videocheckpointid' => $activity->id,
            'userid' => $userid,
        ]);
        $duration = max($duration, (int)($existing->duration ?? 0));
        $percent = $duration > 0 ? min(100, (int)floor(($watched / $duration) * 100)) : 0;
        $required = $DB->get_records('videocheckpoint_cues', [
            'videocheckpointid' => $activity->id,
            'required' => 1,
            'status' => 'published',
        ], '', 'id');
        $answered = 0;
        foreach ($required as $cue) {
            if (
                $DB->record_exists('videocheckpoint_answers', [
                'cueid' => $cue->id,
                'userid' => $userid,
                'correct' => 1,
                ])
            ) {
                $answered++;
            }
        }
        $questionscomplete = count($required) === 0 || $answered === count($required);
        $watchcomplete = empty($activity->completionwatch) || $percent >= (int)$activity->completionpercent;
        $allquestionscomplete = empty($activity->completionquestions) || $questionscomplete;

        $record = (object) [
            'videocheckpointid' => $activity->id,
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
            $DB->update_record('videocheckpoint_progress', $record);
        } else {
            $record->timecreated = time();
            $record->id = $DB->insert_record('videocheckpoint_progress', $record);
        }
        if ($record->grade !== null) {
            \videocheckpoint_grade_item_update($activity, [(object) [
                'userid' => $userid,
                'rawgrade' => $record->grade,
            ], ]);
        }
        self::update_completion($activity, $userid);
        return $record;
    }

    /**
     * Recalculate every learner who has stored activity data.
     *
     * This keeps grades and completion accurate when completion settings or
     * published checkpoint requirements change after learners have started.
     */
    public static function recalculate_all(\stdClass $activity): void {
        global $DB;
        $sql = "SELECT userid
                  FROM {videocheckpoint_progress}
                 WHERE videocheckpointid = ?
                 UNION
                SELECT userid
                  FROM {videocheckpoint_segments}
                 WHERE videocheckpointid = ?
                 UNION
                SELECT a.userid
                  FROM {videocheckpoint_answers} a
                  JOIN {videocheckpoint_cues} c ON c.id = a.cueid
                 WHERE c.videocheckpointid = ?";
        $userids = $DB->get_fieldset_sql($sql, [$activity->id, $activity->id, $activity->id]);
        foreach ($userids as $userid) {
            self::recalculate($activity, (int)$userid);
        }
    }

    /**
     * Calculates unique watched seconds across overlapping intervals.
     *
     * @param array $segments Playback intervals.
     * @return int Unique watched seconds.
     */
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

    /**
     * Asks Moodle to recalculate activity completion.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid User ID.
     */
    private static function update_completion(\stdClass $activity, int $userid): void {
        $cm = get_coursemodule_from_instance('videocheckpoint', $activity->id, $activity->course, false, MUST_EXIST);
        $course = get_course($activity->course);
        $completion = new \completion_info($course);
        $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
    }
}
