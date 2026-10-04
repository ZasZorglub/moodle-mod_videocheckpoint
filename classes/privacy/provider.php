<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('interactivevideo_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'interactivevideoid' => 'privacy:metadata:progress:activity',
            'watchedseconds' => 'privacy:metadata:progress:watched',
            'percent' => 'privacy:metadata:progress:watched',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('interactivevideo_answers', [
            'userid' => 'privacy:metadata:progress:userid',
            'answer' => 'privacy:metadata:answers:answer',
            'correct' => 'privacy:metadata:answers:answer',
        ], 'privacy:metadata:answers');
        $collection->add_database_table('interactivevideo_segments', [
            'userid' => 'privacy:metadata:progress:userid',
            'interactivevideoid' => 'privacy:metadata:progress:activity',
            'starttime' => 'privacy:metadata:progress:watched',
            'endtime' => 'privacy:metadata:progress:watched',
        ], 'privacy:metadata:progress');
        $collection->add_external_location_link('vimeo', [], 'privacy:metadata:external:vimeo');
        $collection->add_external_location_link('aiprovider', [], 'privacy:metadata:external:ai');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {interactivevideo_progress} p ON p.interactivevideoid = cm.instance
                 WHERE p.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'interactivevideo', 'userid' => $userid];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('interactivevideo', $context->instanceid, 0, false, MUST_EXIST);
            $progress = $DB->get_record('interactivevideo_progress', [
                'interactivevideoid' => $cm->instance,
                'userid' => $contextlist->get_user()->id,
            ]);
            if ($progress) {
                unset($progress->id, $progress->userid);
                $progress->timecreated = transform::datetime($progress->timecreated);
                $progress->timemodified = transform::datetime($progress->timemodified);
                writer::with_context($context)->export_data([], $progress);
            }
            $segments = array_values($DB->get_records('interactivevideo_segments', [
                'interactivevideoid' => $cm->instance,
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
                   FROM {interactivevideo_answers} a
                   JOIN {interactivevideo_cues} c ON c.id = a.cueid
                  WHERE c.interactivevideoid = :activityid AND a.userid = :userid
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

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('interactivevideo', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $cueids = $DB->get_fieldset_select('interactivevideo_cues', 'id', 'interactivevideoid = ?', [$cm->instance]);
        if ($cueids) {
            [$insql, $params] = $DB->get_in_or_equal($cueids);
            $DB->delete_records_select('interactivevideo_answers', "cueid $insql", $params);
        }
        $DB->delete_records('interactivevideo_segments', ['interactivevideoid' => $cm->instance]);
        $DB->delete_records('interactivevideo_progress', ['interactivevideoid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('interactivevideo', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $cueids = $DB->get_fieldset_select('interactivevideo_cues', 'id', 'interactivevideoid = ?', [$cm->instance]);
            if ($cueids) {
                [$insql, $params] = $DB->get_in_or_equal($cueids);
                $params[] = $userid;
                $DB->delete_records_select('interactivevideo_answers', "cueid $insql AND userid = ?", $params);
            }
            $DB->delete_records('interactivevideo_segments', ['interactivevideoid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('interactivevideo_progress', ['interactivevideoid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
