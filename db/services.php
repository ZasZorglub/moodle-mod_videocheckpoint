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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_videocheckpoint_record_progress' => [
        'classname' => \mod_videocheckpoint\external\record_progress::class,
        'description' => 'Record a verified playback interval and return calculated progress.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videocheckpoint:view',
    ],
    'mod_videocheckpoint_submit_answer' => [
        'classname' => \mod_videocheckpoint\external\submit_answer::class,
        'description' => 'Submit an answer to an interactive video checkpoint.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videocheckpoint:view',
    ],
];
