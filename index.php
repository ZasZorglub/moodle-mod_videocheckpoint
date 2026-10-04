<?php
// This file is part of Moodle - https://moodle.org/

require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);
$PAGE->set_url('/mod/interactivevideo/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'interactivevideo'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'interactivevideo'));
$instances = get_all_instances_in_course('interactivevideo', $course);
$table = new html_table();
$table->head = [get_string('name'), get_string('progress', 'interactivevideo')];
foreach ($instances as $instance) {
    $table->data[] = [
        html_writer::link(new moodle_url('/mod/interactivevideo/view.php', ['id' => $instance->coursemodule]),
            format_string($instance->name)),
        '-',
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
