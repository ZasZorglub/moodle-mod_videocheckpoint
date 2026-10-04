<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

final class ai_generation_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'aigeneration', get_string('aigeneration', 'interactivevideo'));
        $mform->addElement('static', 'aihelp', '', get_string('aigeneration_help', 'interactivevideo'));
        $mform->addElement('textarea', 'transcript', get_string('transcript', 'interactivevideo'), [
            'rows' => 14,
            'cols' => 90,
        ]);
        $mform->setType('transcript', PARAM_RAW);
        $mform->addRule('transcript', null, 'required', null, 'client');
        $mform->setDefault('transcript', $this->_customdata['transcript'] ?? '');
        $mform->addElement('select', 'numberofquestions', get_string('numberofquestions', 'interactivevideo'), [
            2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8,
        ]);
        $mform->setDefault('numberofquestions', 4);
        $this->add_action_buttons(false, get_string('generatequestions', 'interactivevideo'));
    }
}
