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

namespace mod_videocheckpoint\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for generating draft checkpoint questions with Moodle AI.
 */
final class ai_generation_form extends \moodleform {
    /**
     * Defines the AI generation form fields.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'aigeneration', get_string('aigeneration', 'videocheckpoint'));
        $mform->addElement('static', 'aihelp', '', get_string('aigeneration_help', 'videocheckpoint'));
        $mform->addElement('textarea', 'transcript', get_string('transcript', 'videocheckpoint'), [
            'rows' => 14,
            'cols' => 90,
        ]);
        $mform->setType('transcript', PARAM_RAW);
        $mform->addRule('transcript', null, 'required', null, 'client');
        $mform->setDefault('transcript', $this->_customdata['transcript'] ?? '');
        $mform->addElement('select', 'numberofquestions', get_string('numberofquestions', 'videocheckpoint'), [
            2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8,
        ]);
        $mform->setDefault('numberofquestions', 4);
        $this->add_action_buttons(false, get_string('generatequestions', 'videocheckpoint'));
    }
}
