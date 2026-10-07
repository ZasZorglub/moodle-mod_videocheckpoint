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
$action = optional_param('action', '', PARAM_ALPHA);
$cueid = optional_param('cueid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videocheckpoint', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videocheckpoint', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videocheckpoint:managequestions', $context);

$PAGE->set_url('/mod/videocheckpoint/manage.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managequestions', 'videocheckpoint'));
$PAGE->set_heading(format_string($course->fullname));

if ($action !== '' && $cueid) {
    require_sesskey();
    $cue = $DB->get_record('videocheckpoint_cues', [
        'id' => $cueid,
        'videocheckpointid' => $activity->id,
        'status' => 'draft',
    ], '*', MUST_EXIST);
    if ($action === 'publish') {
        $DB->set_field('videocheckpoint_cues', 'status', 'published', ['id' => $cue->id]);
        \mod_videocheckpoint\local\progress::recalculate_all($activity);
    } else if ($action === 'delete') {
        $DB->delete_records('videocheckpoint_answers', ['cueid' => $cue->id]);
        $DB->delete_records('videocheckpoint_cues', ['id' => $cue->id]);
    }
    redirect($PAGE->url);
}

$form = new \mod_videocheckpoint\form\ai_generation_form(null, ['transcript' => $activity->transcript]);
if ($data = $form->get_data()) {
    try {
        $ids = \mod_videocheckpoint\local\ai_question_generator::generate(
            $activity,
            $USER->id,
            $data->transcript,
            (int)$data->numberofquestions,
        );
        redirect(
            $PAGE->url,
            get_string('aidraftsgenerated', 'videocheckpoint', count($ids)),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\Throwable $exception) {
        redirect(
            $PAGE->url,
            get_string('aigenerationfailed', 'videocheckpoint', $exception->getMessage()),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managequestions', 'videocheckpoint'));
$form->display();
echo $OUTPUT->heading(get_string('aidrafts', 'videocheckpoint'), 3);
$drafts = $DB->get_records('videocheckpoint_cues', [
    'videocheckpointid' => $activity->id,
    'status' => 'draft',
], 'timeposition, id');
if (!$drafts) {
    echo $OUTPUT->notification(get_string('nodrafts', 'videocheckpoint'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('cueminute', 'videocheckpoint') . ' / ' . get_string('cuesec', 'videocheckpoint'),
        get_string('questiontext', 'videocheckpoint'),
        get_string('actions'),
    ];
    foreach ($drafts as $draft) {
        $publishurl = new moodle_url($PAGE->url, ['action' => 'publish', 'cueid' => $draft->id, 'sesskey' => sesskey()]);
        $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'cueid' => $draft->id, 'sesskey' => sesskey()]);
        $table->data[] = [
            sprintf('%d:%02d', (int)floor($draft->timeposition / 60), $draft->timeposition % 60),
            format_string($draft->questiontext),
            html_writer::link($publishurl, get_string('publish', 'videocheckpoint')) . ' · '
                . html_writer::link($deleteurl, get_string('delete', 'videocheckpoint')),
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->single_button(
    new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
    get_string('editactivity', 'videocheckpoint'),
    'get'
);
echo $OUTPUT->footer();
