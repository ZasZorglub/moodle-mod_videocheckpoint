<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_interactivevideo_record_progress' => [
        'classname' => \mod_interactivevideo\external\record_progress::class,
        'description' => 'Record a verified playback interval and return calculated progress.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/interactivevideo:view',
    ],
    'mod_interactivevideo_submit_answer' => [
        'classname' => \mod_interactivevideo\external\submit_answer::class,
        'description' => 'Submit an answer to an interactive video checkpoint.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/interactivevideo:view',
    ],
];
