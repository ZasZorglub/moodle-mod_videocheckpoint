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

define('CLI_SCRIPT', true);

require(dirname(__DIR__, 4) . '/config.php');

$activity = $DB->get_record('videocheckpoint', [], '*', IGNORE_MULTIPLE);
if (!$activity) {
    throw new moodle_exception('No interactive video activity is available for regression testing.');
}
$cm = get_coursemodule_from_instance('videocheckpoint', $activity->id, $activity->course, false, MUST_EXIST);
$admin = get_admin();
\core\session\manager::set_user($admin);

$transaction = $DB->start_delegated_transaction();
$failure = null;
try {
    $activity->completionwatch = 0;
    $activity->completionquestions = 1;
    $DB->update_record('videocheckpoint', (object) [
        'id' => $activity->id,
        'completionwatch' => 0,
        'completionquestions' => 1,
    ]);

    $requiredcues = $DB->get_records('videocheckpoint_cues', [
        'videocheckpointid' => $activity->id,
        'required' => 1,
        'status' => 'published',
    ]);
    foreach ($requiredcues as $cue) {
        $DB->delete_records('videocheckpoint_answers', ['cueid' => $cue->id, 'userid' => $admin->id]);
        $DB->insert_record('videocheckpoint_answers', (object) [
            'cueid' => $cue->id,
            'userid' => $admin->id,
            'answer' => $cue->correctanswer,
            'correct' => 1,
            'timecreated' => time(),
        ]);
    }
    \mod_videocheckpoint\local\progress::recalculate($activity, $admin->id);

    $draftid = $DB->insert_record('videocheckpoint_cues', (object) [
        'videocheckpointid' => $activity->id,
        'timeposition' => 1,
        'questiontext' => 'Runtime regression question',
        'optionsjson' => json_encode(['Wrong', 'Correct'], JSON_THROW_ON_ERROR),
        'correctanswer' => 1,
        'feedback' => 'Try again and review the relevant section.',
        'required' => 1,
        'status' => 'draft',
        'sortorder' => 999,
        'timecreated' => time(),
        'timemodified' => time(),
    ]);

    try {
        \mod_videocheckpoint\external\submit_answer::execute($cm->id, $draftid, 1);
        throw new moodle_exception('A draft checkpoint accepted an answer.');
    } catch (dml_missing_record_exception $exception) {
        // Expected: learners cannot submit answers to unpublished checkpoints.
        unset($exception);
    }
    if ($DB->record_exists('videocheckpoint_answers', ['cueid' => $draftid, 'userid' => $admin->id])) {
        throw new moodle_exception('A draft checkpoint stored an answer.');
    }

    $DB->set_field('videocheckpoint_cues', 'status', 'published', ['id' => $draftid]);
    \mod_videocheckpoint\local\progress::recalculate_all($activity);
    $progress = $DB->get_record('videocheckpoint_progress', [
        'videocheckpointid' => $activity->id,
        'userid' => $admin->id,
    ], '*', MUST_EXIST);
    if ($progress->questionscomplete) {
        throw new moodle_exception('Publishing a required checkpoint did not invalidate question completion.');
    }

    $result = \mod_videocheckpoint\external\submit_answer::execute($cm->id, $draftid, 1);
    if (empty($result['correct'])) {
        throw new moodle_exception('The published checkpoint rejected its correct answer.');
    }
    $progress = $DB->get_record('videocheckpoint_progress', [
        'videocheckpointid' => $activity->id,
        'userid' => $admin->id,
    ], '*', MUST_EXIST);
    if (!$progress->questionscomplete) {
        throw new moodle_exception('A correct answer did not restore question completion.');
    }

    $uploadactivity = $DB->get_record('videocheckpoint', ['videosource' => 'upload'], '*', IGNORE_MULTIPLE);
    if (!$uploadactivity) {
        throw new moodle_exception('No uploaded-video activity is available for file regression testing.');
    }
    $uploadcm = get_coursemodule_from_instance(
        'videocheckpoint',
        $uploadactivity->id,
        $uploadactivity->course,
        false,
        MUST_EXIST
    );
    $context = context_module::instance($uploadcm->id);
    $files = get_file_storage()->get_area_files(
        $context->id,
        'mod_videocheckpoint',
        'video',
        0,
        'id',
        false
    );
    if (count($files) !== 1 || reset($files)->get_mimetype() !== 'video/mp4') {
        throw new moodle_exception('The uploaded MP4 is missing from the Moodle File API.');
    }
} catch (Throwable $exception) {
    $failure = $exception;
}

try {
    $transaction->rollback(new RuntimeException('Rollback the runtime regression transaction.'));
} catch (RuntimeException $exception) {
    // Expected: delegated transactions rethrow the exception after rollback.
    unset($exception);
}
if ($failure) {
    throw $failure;
}
echo "PLUGIN_RUNTIME_REGRESSION_PASS draft_security=1 completion_recalculation=1 uploaded_mp4=1" . PHP_EOL;
