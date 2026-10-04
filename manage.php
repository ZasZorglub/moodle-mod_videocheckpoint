<?php
// This file is part of Moodle - https://moodle.org/

require('../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$cueid = optional_param('cueid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('interactivevideo', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('interactivevideo', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/interactivevideo:managequestions', $context);

$PAGE->set_url('/mod/interactivevideo/manage.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managequestions', 'interactivevideo'));
$PAGE->set_heading(format_string($course->fullname));

if ($action !== '' && $cueid) {
    require_sesskey();
    $cue = $DB->get_record('interactivevideo_cues', [
        'id' => $cueid,
        'interactivevideoid' => $activity->id,
        'status' => 'draft',
    ], '*', MUST_EXIST);
    if ($action === 'publish') {
        $DB->set_field('interactivevideo_cues', 'status', 'published', ['id' => $cue->id]);
    } else if ($action === 'delete') {
        $DB->delete_records('interactivevideo_answers', ['cueid' => $cue->id]);
        $DB->delete_records('interactivevideo_cues', ['id' => $cue->id]);
    }
    redirect($PAGE->url);
}

$form = new \mod_interactivevideo\form\ai_generation_form(null, ['transcript' => $activity->transcript]);
if ($data = $form->get_data()) {
    try {
        $ids = \mod_interactivevideo\local\ai_question_generator::generate(
            $activity,
            $USER->id,
            $data->transcript,
            (int)$data->numberofquestions,
        );
        redirect($PAGE->url, get_string('aidraftsgenerated', 'interactivevideo', count($ids)), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Throwable $exception) {
        redirect($PAGE->url, get_string('aigenerationfailed', 'interactivevideo', $exception->getMessage()), null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managequestions', 'interactivevideo'));
$form->display();
echo $OUTPUT->heading(get_string('aidrafts', 'interactivevideo'), 3);
$drafts = $DB->get_records('interactivevideo_cues', [
    'interactivevideoid' => $activity->id,
    'status' => 'draft',
], 'timeposition, id');
if (!$drafts) {
    echo $OUTPUT->notification(get_string('nodrafts', 'interactivevideo'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('cueminute', 'interactivevideo') . ' / ' . get_string('cuesec', 'interactivevideo'),
        get_string('questiontext', 'interactivevideo'),
        get_string('actions')];
    foreach ($drafts as $draft) {
        $publishurl = new moodle_url($PAGE->url, ['action' => 'publish', 'cueid' => $draft->id, 'sesskey' => sesskey()]);
        $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'cueid' => $draft->id, 'sesskey' => sesskey()]);
        $table->data[] = [
            sprintf('%d:%02d', (int)floor($draft->timeposition / 60), $draft->timeposition % 60),
            format_string($draft->questiontext),
            html_writer::link($publishurl, get_string('publish', 'interactivevideo')) . ' · '
                . html_writer::link($deleteurl, get_string('delete', 'interactivevideo')),
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->single_button(new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]),
    get_string('editactivity', 'interactivevideo'), 'get');
echo $OUTPUT->footer();
