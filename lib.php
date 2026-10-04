<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

function interactivevideo_supports(string $feature): bool|string|null {
    return match ($feature) {
        FEATURE_MOD_INTRO,
        FEATURE_SHOW_DESCRIPTION,
        FEATURE_COMPLETION_TRACKS_VIEWS,
        FEATURE_COMPLETION_HAS_RULES,
        FEATURE_GRADE_HAS_GRADE,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

function interactivevideo_add_instance(stdClass $data, ?mod_interactivevideo_mod_form $mform = null): int {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('interactivevideo', $data);
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $data->coursemodule]);
    $context = context_module::instance($data->coursemodule);
    interactivevideo_save_video_file($data, $context);
    interactivevideo_save_cues($data);
    interactivevideo_grade_item_update($data);
    return $data->id;
}

function interactivevideo_update_instance(stdClass $data, ?mod_interactivevideo_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('interactivevideo', $data);
    $context = context_module::instance($data->coursemodule);
    interactivevideo_save_video_file($data, $context);
    interactivevideo_save_cues($data);
    interactivevideo_grade_item_update($data);
    return $result;
}

function interactivevideo_delete_instance(int $id): bool {
    global $DB;
    if (!$activity = $DB->get_record('interactivevideo', ['id' => $id])) {
        return false;
    }
    $cueids = $DB->get_fieldset_select('interactivevideo_cues', 'id', 'interactivevideoid = ?', [$id]);
    if ($cueids) {
        [$insql, $params] = $DB->get_in_or_equal($cueids);
        $DB->delete_records_select('interactivevideo_answers', "cueid $insql", $params);
    }
    $DB->delete_records('interactivevideo_segments', ['interactivevideoid' => $id]);
    $DB->delete_records('interactivevideo_progress', ['interactivevideoid' => $id]);
    $DB->delete_records('interactivevideo_cues', ['interactivevideoid' => $id]);
    $DB->delete_records('interactivevideo', ['id' => $id]);
    interactivevideo_grade_item_delete($activity);
    return true;
}

function interactivevideo_save_video_file(stdClass $data, context_module $context): void {
    if (!empty($data->videofile)) {
        file_save_draft_area_files($data->videofile, $context->id, 'mod_interactivevideo', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4'],
        ]);
    }
}

function interactivevideo_save_cues(stdClass $data): void {
    global $DB;
    $existing = $DB->get_records('interactivevideo_cues', ['interactivevideoid' => $data->id]);
    $kept = [];
    foreach (($data->questiontext ?? []) as $index => $questiontext) {
        $questiontext = trim((string)$questiontext);
        if ($questiontext === '') {
            continue;
        }
        $options = array_values(array_filter([
            trim((string)($data->optiona[$index] ?? '')),
            trim((string)($data->optionb[$index] ?? '')),
            trim((string)($data->optionc[$index] ?? '')),
            trim((string)($data->optiond[$index] ?? '')),
        ], static fn(string $option): bool => $option !== ''));
        if (count($options) < 2) {
            continue;
        }
        $cueid = (int)($data->cueid[$index] ?? 0);
        $cue = (object) [
            'interactivevideoid' => $data->id,
            'timeposition' => max(0, (int)($data->cuetime[$index] ?? 0)),
            'questiontext' => $questiontext,
            'optionsjson' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'correctanswer' => min((int)($data->correctanswer[$index] ?? 0), count($options) - 1),
            'feedback' => trim((string)($data->feedback[$index] ?? '')),
            'required' => empty($data->requiredquestion[$index]) ? 0 : 1,
            'status' => 'published',
            'sortorder' => $index,
            'timemodified' => time(),
        ];
        if ($cueid && isset($existing[$cueid])) {
            $cue->id = $cueid;
            $cue->timecreated = $existing[$cueid]->timecreated;
            $DB->update_record('interactivevideo_cues', $cue);
            $kept[] = $cueid;
        } else {
            $cue->timecreated = time();
            $kept[] = $DB->insert_record('interactivevideo_cues', $cue);
        }
    }
    foreach (array_diff(array_keys($existing), $kept) as $deletedid) {
        $DB->delete_records('interactivevideo_answers', ['cueid' => $deletedid]);
        $DB->delete_records('interactivevideo_cues', ['id' => $deletedid]);
    }
}

function interactivevideo_pluginfile($course, $cm, $context, string $filearea, array $args,
        bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'video') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/interactivevideo:view', $context);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'mod_interactivevideo', 'video', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAY_SECS, 0, false, $options);
}

function interactivevideo_get_coursemodule_info(stdClass $coursemodule): cached_cm_info|false {
    global $DB;
    $activity = $DB->get_record('interactivevideo', ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completionwatch,completionpercent,completionquestions');
    if (!$activity) {
        return false;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('interactivevideo', $activity, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionwatch' => $activity->completionwatch,
            'completionquestions' => $activity->completionquestions,
            'completionpercent' => $activity->completionpercent,
        ];
    }
    return $info;
}

function interactivevideo_get_completion_active_rule_descriptions($cm): array {
    $rules = [];
    if (!empty($cm->customdata['customcompletionrules']['completionwatch'])) {
        $rules[] = get_string('completiondetail:watch', 'interactivevideo', $cm->customdata['completionpercent'] ?? 90);
    }
    if (!empty($cm->customdata['customcompletionrules']['completionquestions'])) {
        $rules[] = get_string('completiondetail:questions', 'interactivevideo');
    }
    return $rules;
}

function interactivevideo_grade_item_update(stdClass $activity, ?array $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = [
        'itemname' => $activity->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => (float)$activity->grade,
        'grademin' => 0,
    ];
    return grade_update('mod/interactivevideo', $activity->course, 'mod', 'interactivevideo', $activity->id, 0, $grades, $params);
}

function interactivevideo_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/interactivevideo', $activity->course, 'mod', 'interactivevideo', $activity->id, 0, null, ['deleted' => 1]);
}
