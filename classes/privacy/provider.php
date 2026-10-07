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

namespace mod_videocheckpoint\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for learner progress and answers.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * {@inheritdoc}
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videocheckpoint_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'videocheckpointid' => 'privacy:metadata:progress:activity',
            'watchedseconds' => 'privacy:metadata:progress:watched',
            'percent' => 'privacy:metadata:progress:watched',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('videocheckpoint_answers', [
            'userid' => 'privacy:metadata:progress:userid',
            'answer' => 'privacy:metadata:answers:answer',
            'correct' => 'privacy:metadata:answers:answer',
        ], 'privacy:metadata:answers');
        $collection->add_database_table('videocheckpoint_segments', [
            'userid' => 'privacy:metadata:progress:userid',
            'videocheckpointid' => 'privacy:metadata:progress:activity',
            'starttime' => 'privacy:metadata:progress:watched',
            'endtime' => 'privacy:metadata:progress:watched',
        ], 'privacy:metadata:progress');
        $collection->add_external_location_link('vimeo', [], 'privacy:metadata:external:vimeo');
        $collection->add_external_location_link('aiprovider', [], 'privacy:metadata:external:ai');
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videocheckpoint_progress} p ON p.videocheckpointid = cm.instance
                 WHERE p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'videocheckpoint', 'userid' => $userid];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * {@inheritdoc}
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videocheckpoint', $context->instanceid, 0, false, MUST_EXIST);
            $progress = $DB->get_record('videocheckpoint_progress', [
                'videocheckpointid' => $cm->instance,
                'userid' => $contextlist->get_user()->id,
            ]);
            if ($progress) {
                unset($progress->id, $progress->userid);
                $progress->timecreated = transform::datetime($progress->timecreated);
                $progress->timemodified = transform::datetime($progress->timemodified);
                writer::with_context($context)->export_data([], $progress);
            }
            $segments = array_values($DB->get_records('videocheckpoint_segments', [
                'videocheckpointid' => $cm->instance,
                'userid' => $contextlist->get_user()->id,
            ], 'starttime, id', 'starttime,endtime,timecreated'));
            if ($segments) {
                foreach ($segments as $segment) {
                    $segment->timecreated = transform::datetime($segment->timecreated);
                }
                writer::with_context($context)->export_data(['playback_segments'], (object) ['segments' => $segments]);
            }
            $answers = $DB->get_records_sql(
                "SELECT a.id, c.questiontext, a.answer, a.correct, a.timecreated
                   FROM {videocheckpoint_answers} a
                   JOIN {videocheckpoint_cues} c ON c.id = a.cueid
                  WHERE c.videocheckpointid = :activityid AND a.userid = :userid
               ORDER BY a.timecreated, a.id",
                ['activityid' => $cm->instance, 'userid' => $contextlist->get_user()->id]
            );
            if ($answers) {
                foreach ($answers as $answer) {
                    unset($answer->id);
                    $answer->timecreated = transform::datetime($answer->timecreated);
                }
                writer::with_context($context)->export_data(['answers'], (object) ['answers' => array_values($answers)]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videocheckpoint', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $cueids = $DB->get_fieldset_select('videocheckpoint_cues', 'id', 'videocheckpointid = ?', [$cm->instance]);
        if ($cueids) {
            [$insql, $params] = $DB->get_in_or_equal($cueids);
            $DB->delete_records_select('videocheckpoint_answers', "cueid $insql", $params);
        }
        $DB->delete_records('videocheckpoint_segments', ['videocheckpointid' => $cm->instance]);
        $DB->delete_records('videocheckpoint_progress', ['videocheckpointid' => $cm->instance]);
    }

    /**
     * {@inheritdoc}
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videocheckpoint', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $cueids = $DB->get_fieldset_select('videocheckpoint_cues', 'id', 'videocheckpointid = ?', [$cm->instance]);
            if ($cueids) {
                [$insql, $params] = $DB->get_in_or_equal($cueids);
                $params[] = $userid;
                $DB->delete_records_select('videocheckpoint_answers', "cueid $insql AND userid = ?", $params);
            }
            $DB->delete_records('videocheckpoint_segments', ['videocheckpointid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('videocheckpoint_progress', ['videocheckpointid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
