<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_interactivevideo_mod_form extends moodleform_mod {
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name', 'interactivevideo'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videosettings', get_string('videosettings', 'interactivevideo'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'interactivevideo'), [
            'upload' => get_string('sourceupload', 'interactivevideo'),
            'vimeo' => get_string('sourcevimeo', 'interactivevideo'),
        ]);
        $mform->setDefault('videosource', 'upload');

        $fileoptions = [
            'accepted_types' => ['.mp4'],
            'maxfiles' => 1,
            'subdirs' => 0,
        ];
        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'interactivevideo'), null, $fileoptions);
        $mform->addHelpButton('videofile', 'videofile', 'interactivevideo');
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');

        $mform->addElement('url', 'videourl', get_string('videourl', 'interactivevideo'), ['size' => 64]);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'interactivevideo');
        $mform->hideIf('videourl', 'videosource', 'neq', 'vimeo');

        $mform->addElement('textarea', 'transcript', get_string('transcript', 'interactivevideo'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('transcript', PARAM_RAW);
        $mform->addHelpButton('transcript', 'transcript', 'interactivevideo');

        $mform->addElement('header', 'checkpoints', get_string('checkpoints', 'interactivevideo'));
        $repeat = [];
        $repeat[] = $mform->createElement('hidden', 'cueid', 0);
        $repeat[] = $mform->createElement('select', 'cueminute', get_string('cueminute', 'interactivevideo'),
            array_combine(range(0, 180), range(0, 180)));
        $repeat[] = $mform->createElement('select', 'cuesec', get_string('cuesec', 'interactivevideo'),
            array_combine(range(0, 59), range(0, 59)));
        $repeat[] = $mform->createElement('textarea', 'questiontext', get_string('questiontext', 'interactivevideo'), ['rows' => 2, 'cols' => 60]);
        $repeat[] = $mform->createElement('text', 'optiona', get_string('optiona', 'interactivevideo'), ['size' => 50]);
        $repeat[] = $mform->createElement('text', 'optionb', get_string('optionb', 'interactivevideo'), ['size' => 50]);
        $repeat[] = $mform->createElement('text', 'optionc', get_string('optionc', 'interactivevideo'), ['size' => 50]);
        $repeat[] = $mform->createElement('text', 'optiond', get_string('optiond', 'interactivevideo'), ['size' => 50]);
        $repeat[] = $mform->createElement('select', 'correctanswer', get_string('correctanswer', 'interactivevideo'), [
            0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D',
        ]);
        $repeat[] = $mform->createElement('textarea', 'feedback', get_string('feedback', 'interactivevideo'), ['rows' => 2, 'cols' => 60]);
        $repeat[] = $mform->createElement('advcheckbox', 'requiredquestion', get_string('requiredquestion', 'interactivevideo'));

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
        $this->repeat_elements($repeat, $repeatcount, $options, 'cue_repeats', 'cue_add_fields', 1,
            get_string('addcheckpoint', 'interactivevideo'), true);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues): void {
        global $DB;
        if (isset($defaultvalues['completionpercent'])) {
            $defaultvalues['watchpercent'] = (int)$defaultvalues['completionpercent'];
        }
        if (empty($this->_instance)) {
            return;
        }
        $draftid = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area($draftid, $this->context->id, 'mod_interactivevideo', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4'],
        ]);
        $defaultvalues['videofile'] = $draftid;

        $cues = array_values($DB->get_records('interactivevideo_cues', [
            'interactivevideoid' => $this->_instance,
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

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['videosource'] ?? '') === 'vimeo'
                && !\mod_interactivevideo\local\video::vimeo_id($data['videourl'] ?? '')) {
            $errors['videourl'] = get_string('invalidvimeourl', 'interactivevideo');
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
        }
        return $errors;
    }

    public function add_completion_rules(): array {
        $mform = $this->_form;
        $watch = 'completionwatch' . $this->get_suffix();
        $questions = 'completionquestions' . $this->get_suffix();
        $mform->addElement('advcheckbox', $watch, '', get_string('completionwatch', 'interactivevideo'));
        $mform->setDefault($watch, 1);
        $mform->addElement('text', 'watchpercent', get_string('completionpercent', 'interactivevideo'), ['size' => 4]);
        $mform->setType('watchpercent', PARAM_INT);
        $mform->setDefault('watchpercent', 90);
        $mform->hideIf('watchpercent', $watch, 'notchecked');
        $mform->addElement('advcheckbox', $questions, '', get_string('completionquestions', 'interactivevideo'));
        $mform->setDefault($questions, 1);
        return [$watch, $questions];
    }

    public function completion_rule_enabled($data): bool {
        return !empty($data['completionwatch' . $this->get_suffix()])
            || !empty($data['completionquestions' . $this->get_suffix()]);
    }

    private function count_existing_cues(): int {
        global $DB;
        return $DB->count_records('interactivevideo_cues', ['interactivevideoid' => $this->_instance]);
    }
}
