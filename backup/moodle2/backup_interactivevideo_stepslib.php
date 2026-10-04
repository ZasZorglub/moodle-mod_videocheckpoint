<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

class backup_interactivevideo_activity_structure_step extends backup_activity_structure_step {
    protected function define_structure(): backup_nested_element {
        $activity = new backup_nested_element('interactivevideo', ['id'], [
            'name', 'intro', 'introformat', 'videosource', 'videourl', 'transcript', 'completionwatch',
            'completionpercent', 'completionquestions', 'grade', 'timecreated', 'timemodified',
        ]);
        $cues = new backup_nested_element('cues');
        $cue = new backup_nested_element('cue', ['id'], [
            'timeposition', 'questiontext', 'optionsjson', 'correctanswer', 'feedback', 'required', 'status', 'sortorder',
            'timecreated', 'timemodified',
        ]);
        $activity->add_child($cues);
        $cues->add_child($cue);
        $activity->set_source_table('interactivevideo', ['id' => backup::VAR_ACTIVITYID]);
        $cue->set_source_table('interactivevideo_cues', ['interactivevideoid' => backup::VAR_PARENTID]);
        $activity->annotate_files('mod_interactivevideo', 'intro', null);
        $activity->annotate_files('mod_interactivevideo', 'video', 0);
        return $this->prepare_activity_structure($activity);
    }
}
