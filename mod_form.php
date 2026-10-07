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

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity configuration form for Video Checkpoint.
 */
class mod_videocheckpoint_mod_form extends moodleform_mod {
    /**
     * Defines the activity form fields.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name', 'videocheckpoint'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'videocheckpoint'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videocheckpoint'), [
            'upload' => get_string('sourceupload', 'videocheckpoint'),
            'vimeo' => get_string('sourcevimeo', 'videocheckpoint'),
        ]);
        $mform->setDefault('videosource', 'upload');

        $fileoptions = [
            'accepted_types' => ['.mp4'],
            'maxfiles' => 1,
            'subdirs' => 0,
        ];
        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videocheckpoint'), null, $fileoptions);
        $mform->addHelpButton('videofile', 'videofile', 'videocheckpoint');
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');

        $mform->addElement('url', 'videourl', get_string('videourl', 'videocheckpoint'), ['size' => 64]);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'videocheckpoint');
        $mform->hideIf('videourl', 'videosource', 'neq', 'vimeo');

        $mform->addElement('textarea', 'transcript', get_string('transcript', 'videocheckpoint'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('transcript', PARAM_RAW);
        $mform->addHelpButton('transcript', 'transcript', 'videocheckpoint');

        $mform->addElement('header', 'checkpoints', get_string('checkpoints', 'videocheckpoint'));
        $repeat = [];
        $repeat[] = $mform->createElement('hidden', 'cueid', 0);
        $repeat[] = $mform->createElement(
            'select',
            'cueminute',
            get_string('cueminute', 'videocheckpoint'),
            array_combine(range(0, 180), range(0, 180))
        );
        $repeat[] = $mform->createElement(
            'select',
            'cuesec',
            get_string('cuesec', 'videocheckpoint'),
            array_combine(range(0, 59), range(0, 59))
        );
        $repeat[] = $mform->createElement(
            'textarea',
            'questiontext',
            get_string('questiontext', 'videocheckpoint'),
            ['rows' => 2, 'cols' => 60]
        );
        $repeat[] = $mform->createElement(
            'text',
            'optiona',
            get_string('optiona', 'videocheckpoint'),
            ['size' => 50]
        );
        $repeat[] = $mform->createElement(
            'text',
            'optionb',
            get_string('optionb', 'videocheckpoint'),
            ['size' => 50]
        );
        $repeat[] = $mform->createElement(
            'text',
            'optionc',
            get_string('optionc', 'videocheckpoint'),
            ['size' => 50]
        );
        $repeat[] = $mform->createElement(
            'text',
            'optiond',
            get_string('optiond', 'videocheckpoint'),
            ['size' => 50]
        );
        $repeat[] = $mform->createElement('select', 'correctanswer', get_string('correctanswer', 'videocheckpoint'), [
            0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D',
        ]);
        $repeat[] = $mform->createElement(
            'textarea',
            'feedback',
            get_string('feedback', 'videocheckpoint'),
            ['rows' => 2, 'cols' => 60]
        );
        $repeat[] = $mform->createElement(
            'advcheckbox',
            'requiredquestion',
            get_string('requiredquestion', 'videocheckpoint')
        );

        $options = [
            'cueid' => ['type' => PARAM_INT],
            'cueminute' => ['type' => PARAM_INT],
            'cuesec' => ['type' => PARAM_INT],
            'questiontext' => ['type' => PARAM_TEXT],
            'optiona' => ['type' => PARAM_TEXT],
            'optionb' => ['type' => PARAM_TEXT],
            'optionc' => ['type' => PARAM_TEXT],
            'optiond' => ['type' => PARAM_TEXT],
            'correctanswer' => ['type' => PARAM_INT],
            'feedback' => ['type' => PARAM_TEXT],
            'requiredquestion' => ['type' => PARAM_BOOL, 'default' => 1],
        ];
        $repeatcount = $this->_instance ? max(2, $this->count_existing_cues()) : 2;
        $this->repeat_elements(
            $repeat,
            $repeatcount,
            $options,
            'cue_repeats',
            'cue_add_fields',
            1,
            get_string('addcheckpoint', 'videocheckpoint'),
            true
        );

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepares stored values for editing.
     *
     * @param array $defaultvalues Default form values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        global $DB;
        if (isset($defaultvalues['completionpercent'])) {
            $defaultvalues['watchpercent'] = (int)$defaultvalues['completionpercent'];
        }
        if (empty($this->_instance)) {
            return;
        }
        $draftid = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area($draftid, $this->context->id, 'mod_videocheckpoint', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4'],
        ]);
        $defaultvalues['videofile'] = $draftid;

        $cues = array_values($DB->get_records('videocheckpoint_cues', [
            'videocheckpointid' => $this->_instance,
        ], 'sortorder, timeposition, id'));
        foreach ($cues as $index => $cue) {
            $answers = json_decode($cue->optionsjson, true) ?: [];
            $defaultvalues["cueid[$index]"] = $cue->id;
            $defaultvalues["cueminute[$index]"] = (int)floor($cue->timeposition / 60);
            $defaultvalues["cuesec[$index]"] = $cue->timeposition % 60;
            $defaultvalues["questiontext[$index]"] = $cue->questiontext;
            foreach (['a', 'b', 'c', 'd'] as $answerindex => $letter) {
                $defaultvalues["option{$letter}[$index]"] = $answers[$answerindex] ?? '';
            }
            $defaultvalues["correctanswer[$index]"] = $cue->correctanswer;
            $defaultvalues["feedback[$index]"] = $cue->feedback;
            $defaultvalues["requiredquestion[$index]"] = $cue->required;
        }
    }

    /**
     * Validates submitted activity settings.
     *
     * @param array $data Submitted form values.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['videosource'] ?? '') === 'upload') {
            $draftid = (int)($data['videofile'] ?? 0);
            $draftinfo = $draftid ? file_get_draft_area_info($draftid) : ['filecount' => 0];
            if (empty($draftinfo['filecount'])) {
                $errors['videofile'] = get_string('required');
            }
        }
        if (
            ($data['videosource'] ?? '') === 'vimeo'
                && !\mod_videocheckpoint\local\video::vimeo_id($data['videourl'] ?? '')
        ) {
            $errors['videourl'] = get_string('invalidvimeourl', 'videocheckpoint');
        }
        $watchpercent = (int)($data['watchpercent'] ?? 90);
        if ($watchpercent < 1 || $watchpercent > 100) {
            $errors['watchpercent'] = get_string('invalidwatchpercent', 'videocheckpoint');
        }
        foreach (($data['questiontext'] ?? []) as $index => $question) {
            if (trim($question) === '') {
                continue;
            }
            $minute = (int)($data['cueminute'][$index] ?? -1);
            $second = (int)($data['cuesec'][$index] ?? -1);
            if ($minute < 0 || $minute > 180) {
                $errors["cueminute[$index]"] = get_string('invaliddata', 'error');
            }
            if ($second < 0 || $second > 59) {
                $errors["cuesec[$index]"] = get_string('invaliddata', 'error');
            }
            if (trim($data['optiona'][$index] ?? '') === '' || trim($data['optionb'][$index] ?? '') === '') {
                $errors["optiona[$index]"] = get_string('required');
            }
            $optionkeys = ['optiona', 'optionb', 'optionc', 'optiond'];
            $correctanswer = (int)($data['correctanswer'][$index] ?? -1);
            if (
                $correctanswer < 0 || $correctanswer > 3
                    || trim($data[$optionkeys[$correctanswer]][$index] ?? '') === ''
            ) {
                $errors["correctanswer[$index]"] = get_string('invalidcorrectanswer', 'videocheckpoint');
            }
            $emptyoptionfound = false;
            foreach ($optionkeys as $optionkey) {
                if (trim($data[$optionkey][$index] ?? '') === '') {
                    $emptyoptionfound = true;
                } else if ($emptyoptionfound) {
                    $errors["{$optionkey}[$index]"] = get_string('optiongap', 'videocheckpoint');
                }
            }
        }
        return $errors;
    }

    /**
     * Adds custom completion controls.
     *
     * @return array Names of the custom completion fields.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $watch = 'completionwatch' . $this->get_suffix();
        $questions = 'completionquestions' . $this->get_suffix();
        $mform->addElement('advcheckbox', $watch, '', get_string('completionwatch', 'videocheckpoint'));
        $mform->setDefault($watch, 1);
        $mform->addElement('text', 'watchpercent', get_string('completionpercent', 'videocheckpoint'), ['size' => 4]);
        $mform->setType('watchpercent', PARAM_INT);
        $mform->setDefault('watchpercent', 90);
        $mform->hideIf('watchpercent', $watch, 'notchecked');
        $mform->addElement('advcheckbox', $questions, '', get_string('completionquestions', 'videocheckpoint'));
        $mform->setDefault($questions, 1);
        return [$watch, $questions];
    }

    /**
     * Reports whether at least one custom completion rule is enabled.
     *
     * @param array $data Submitted form values.
     * @return bool Whether a custom completion rule is enabled.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionwatch' . $this->get_suffix()])
            || !empty($data['completionquestions' . $this->get_suffix()]);
    }

    /**
     * Counts the checkpoints already stored for the activity.
     *
     * @return int Number of stored checkpoints.
     */
    private function count_existing_cues(): int {
        global $DB;
        return $DB->count_records('videocheckpoint_cues', ['videocheckpointid' => $this->_instance]);
    }
}
