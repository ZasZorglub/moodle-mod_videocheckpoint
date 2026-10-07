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

/**
 * Declares supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return bool|string|null Support status or feature value.
 */
function videocheckpoint_supports(string $feature): bool|string|null {
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

/**
 * Creates a Video Checkpoint activity.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videocheckpoint_mod_form|null $mform Activity form.
 * @return int New activity instance ID.
 */
function videocheckpoint_add_instance(stdClass $data, ?mod_videocheckpoint_mod_form $mform = null): int {
    global $DB;
    unset($mform);
    $data->completionpercent = (int)($data->watchpercent ?? 90);
    unset($data->watchpercent);
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('videocheckpoint', $data);
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $data->coursemodule]);
    $context = context_module::instance($data->coursemodule);
    videocheckpoint_save_video_file($data, $context);
    videocheckpoint_save_cues($data);
    videocheckpoint_grade_item_update($data);
    return $data->id;
}

/**
 * Updates a Video Checkpoint activity.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videocheckpoint_mod_form|null $mform Activity form.
 * @return bool Whether the database update succeeded.
 */
function videocheckpoint_update_instance(stdClass $data, ?mod_videocheckpoint_mod_form $mform = null): bool {
    global $DB;
    unset($mform);
    $data->completionpercent = (int)($data->watchpercent ?? $DB->get_field(
        'videocheckpoint',
        'completionpercent',
        ['id' => $data->instance]
    ));
    unset($data->watchpercent);
    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('videocheckpoint', $data);
    $context = context_module::instance($data->coursemodule);
    videocheckpoint_save_video_file($data, $context);
    videocheckpoint_save_cues($data);
    $activity = $DB->get_record('videocheckpoint', ['id' => $data->id], '*', MUST_EXIST);
    videocheckpoint_grade_item_update($activity);
    \mod_videocheckpoint\local\progress::recalculate_all($activity);
    return $result;
}

/**
 * Deletes a Video Checkpoint activity and its learner data.
 *
 * @param int $id Activity instance ID.
 * @return bool Whether an activity was deleted.
 */
function videocheckpoint_delete_instance(int $id): bool {
    global $DB;
    if (!$activity = $DB->get_record('videocheckpoint', ['id' => $id])) {
        return false;
    }
    $cueids = $DB->get_fieldset_select('videocheckpoint_cues', 'id', 'videocheckpointid = ?', [$id]);
    if ($cueids) {
        [$insql, $params] = $DB->get_in_or_equal($cueids);
        $DB->delete_records_select('videocheckpoint_answers', "cueid $insql", $params);
    }
    $DB->delete_records('videocheckpoint_segments', ['videocheckpointid' => $id]);
    $DB->delete_records('videocheckpoint_progress', ['videocheckpointid' => $id]);
    $DB->delete_records('videocheckpoint_cues', ['videocheckpointid' => $id]);
    $DB->delete_records('videocheckpoint', ['id' => $id]);
    videocheckpoint_grade_item_delete($activity);
    return true;
}

/**
 * Moves an uploaded MP4 from the draft area into the activity file area.
 *
 * @param stdClass $data Submitted activity data.
 * @param context_module $context Activity context.
 */
function videocheckpoint_save_video_file(stdClass $data, context_module $context): void {
    if (!empty($data->videofile)) {
        file_save_draft_area_files($data->videofile, $context->id, 'mod_videocheckpoint', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4'],
        ]);
    }
}

/**
 * Replaces the activity's configured checkpoint questions.
 *
 * @param stdClass $data Submitted activity data.
 */
function videocheckpoint_save_cues(stdClass $data): void {
    global $DB;
    $existing = $DB->get_records('videocheckpoint_cues', ['videocheckpointid' => $data->id]);
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
            'videocheckpointid' => $data->id,
            'timeposition' => max(0, (int)($data->cueminute[$index] ?? 0) * 60
                + (int)($data->cuesec[$index] ?? 0)),
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
            $DB->update_record('videocheckpoint_cues', $cue);
            $kept[] = $cueid;
        } else {
            $cue->timecreated = time();
            $kept[] = $DB->insert_record('videocheckpoint_cues', $cue);
        }
    }
    foreach (array_diff(array_keys($existing), $kept) as $deletedid) {
        $DB->delete_records('videocheckpoint_answers', ['cueid' => $deletedid]);
        $DB->delete_records('videocheckpoint_cues', ['id' => $deletedid]);
    }
}

/**
 * Serves uploaded activity video files after access checks.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context File context.
 * @param string $filearea Requested file area.
 * @param array $args Remaining path arguments.
 * @param bool $forcedownload Whether a download was requested.
 * @param array $options File-serving options.
 * @return bool False when the request cannot be served.
 */
function videocheckpoint_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'video') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videocheckpoint:view', $context);
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'mod_videocheckpoint', 'video', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * Provides cached course-module information and custom completion data.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|false Cached information or false if missing.
 */
function videocheckpoint_get_coursemodule_info(stdClass $coursemodule): cached_cm_info|false {
    global $DB;
    $activity = $DB->get_record(
        'videocheckpoint',
        ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completionwatch,completionpercent,completionquestions'
    );
    if (!$activity) {
        return false;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('videocheckpoint', $activity, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionwatch' => $activity->completionwatch,
            'completionquestions' => $activity->completionquestions,
        ];
        $info->customdata['watchpercent'] = $activity->completionpercent;
    }
    return $info;
}

/**
 * Returns human-readable active custom completion rules.
 *
 * @param stdClass $cm Course module information.
 * @return array Active rule descriptions.
 */
function videocheckpoint_get_completion_active_rule_descriptions($cm): array {
    $rules = [];
    if (!empty($cm->customdata['customcompletionrules']['completionwatch'])) {
        $rules[] = get_string('completiondetail:watch', 'videocheckpoint', $cm->customdata['watchpercent'] ?? 90);
    }
    if (!empty($cm->customdata['customcompletionrules']['completionquestions'])) {
        $rules[] = get_string('completiondetail:questions', 'videocheckpoint');
    }
    return $rules;
}

/**
 * Creates or updates the activity grade item and optional learner grades.
 *
 * @param stdClass $activity Activity record.
 * @param array|null $grades Optional grade records.
 * @return int Gradebook update status.
 */
function videocheckpoint_grade_item_update(stdClass $activity, ?array $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = [
        'itemname' => $activity->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => (float)$activity->grade,
        'grademin' => 0,
    ];
    return grade_update(
        'mod/videocheckpoint',
        $activity->course,
        'mod',
        'videocheckpoint',
        $activity->id,
        0,
        $grades,
        $params
    );
}

/**
 * Deletes the activity grade item.
 *
 * @param stdClass $activity Activity record.
 * @return int Gradebook update status.
 */
function videocheckpoint_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update(
        'mod/videocheckpoint',
        $activity->course,
        'mod',
        'videocheckpoint',
        $activity->id,
        0,
        null,
        ['deleted' => 1]
    );
}
