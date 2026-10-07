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

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videocheckpoint', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videocheckpoint', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videocheckpoint:view', $context);

$PAGE->set_url('/mod/videocheckpoint/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
$event = \mod_videocheckpoint\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('videocheckpoint', $activity);
$event->trigger();

$cues = array_values($DB->get_records('videocheckpoint_cues', [
    'videocheckpointid' => $activity->id,
    'status' => 'published',
], 'timeposition, sortorder, id'));
$answered = [];
if ($cues) {
    [$insql, $params] = $DB->get_in_or_equal(array_column($cues, 'id'));
    $params[] = $USER->id;
    $answered = array_map('intval', $DB->get_fieldset_select(
        'videocheckpoint_answers',
        'DISTINCT cueid',
        "cueid $insql AND userid = ? AND correct = 1",
        $params
    ));
}
$cuedata = array_map(static function (stdClass $cue): array {
    return [
        'id' => (int)$cue->id,
        'time' => (int)$cue->timeposition,
        'question' => format_string($cue->questiontext),
        'options' => array_values(json_decode($cue->optionsjson, true) ?: []),
        'required' => (bool)$cue->required,
    ];
}, $cues);

$progress = $DB->get_record('videocheckpoint_progress', [
    'videocheckpointid' => $activity->id,
    'userid' => $USER->id,
]);

$player = ['type' => $activity->videosource];
if ($activity->videosource === 'upload') {
    $files = get_file_storage()->get_area_files(
        $context->id,
        'mod_videocheckpoint',
        'video',
        0,
        'itemid, filepath, filename',
        false
    );
    $file = reset($files);
    if ($file) {
        $player['url'] = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_videocheckpoint',
            'video',
            0,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }
} else if ($activity->videosource === 'vimeo') {
    $player['url'] = \mod_videocheckpoint\local\video::vimeo_embed_url((string)$activity->videourl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
if (has_capability('mod/videocheckpoint:managequestions', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/videocheckpoint/manage.php', ['id' => $cm->id]),
        get_string('managequestions', 'videocheckpoint'),
        'get'
    );
}
if (trim((string)$activity->intro) !== '') {
    echo $OUTPUT->box(format_module_intro('videocheckpoint', $activity, $cm->id), 'generalbox mod_introbox');
}
if (empty($player['url'])) {
    echo $OUTPUT->notification(get_string('missingvideo', 'videocheckpoint'), 'error');
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->render_from_template('mod_videocheckpoint/player', [
    'upload' => $player['type'] === 'upload',
    'vimeo' => $player['type'] === 'vimeo',
    'url' => $player['url'],
    'percent' => (int)($progress->percent ?? 0),
    'progresslabel' => get_string('watchedpercent', 'videocheckpoint', (int)($progress->percent ?? 0)),
]);

$PAGE->requires->js_call_amd('mod_videocheckpoint/player', 'init', [[
    'cmid' => (int)$cm->id,
    'type' => $player['type'],
    'cues' => $cuedata,
    'answered' => $answered,
    'strings' => [
        'submit' => get_string('answer', 'videocheckpoint'),
        'continue' => get_string('continuevideo', 'videocheckpoint'),
        'correct' => get_string('correct', 'videocheckpoint'),
        'incorrect' => get_string('incorrect', 'videocheckpoint'),
        'required' => get_string('questionrequired', 'videocheckpoint'),
    ],
], ]);

echo $OUTPUT->footer();
