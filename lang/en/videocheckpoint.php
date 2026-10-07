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

$string['addcheckpoint'] = 'Add another checkpoint';
$string['aidrafts'] = 'AI-generated drafts';
$string['aidraftsgenerated'] = '{$a} draft questions were generated. Review them before publishing.';
$string['aigeneration'] = 'AI-assisted question generation';
$string['aigeneration_help'] = 'The transcript is sent to the AI provider configured by the Moodle administrator. Generated questions remain drafts until a teacher publishes them.';
$string['aigenerationfailed'] = 'The AI provider could not generate valid questions: {$a}';
$string['answer'] = 'Submit answer';
$string['checkpoint'] = 'Checkpoint {$a}';
$string['checkpoints'] = 'Checkpoint questions';
$string['completiondetail:questions'] = 'Answer all mandatory checkpoint questions correctly';
$string['completiondetail:watch'] = 'Watch at least {$a}% of the video';
$string['completionpercent'] = 'Required watched percentage';
$string['completionquestions'] = 'Require all mandatory checkpoint questions to be answered correctly';
$string['completionwatch'] = 'Require a watched percentage';
$string['continuevideo'] = 'Continue video';
$string['correct'] = 'Correct.';
$string['correctanswer'] = 'Correct option';
$string['cueminute'] = 'Minutes';
$string['cuesec'] = 'Seconds';
$string['delete'] = 'Delete';
$string['editactivity'] = 'Edit all activity settings';
$string['feedback'] = 'Feedback after answer';
$string['generatequestions'] = 'Generate draft questions';
$string['incorrect'] = 'That answer is not correct. Try again.';
$string['invalidcorrectanswer'] = 'The selected correct option must contain answer text.';
$string['invalidvimeourl'] = 'Enter a valid Vimeo video URL.';
$string['invalidwatchpercent'] = 'The required watched percentage must be between 1 and 100.';
$string['managequestions'] = 'Manage AI question drafts';
$string['missingvideo'] = 'No playable video is configured.';
$string['modulename'] = 'Video Checkpoint';
$string['modulename_help'] = 'Add an uploaded or Vimeo-hosted video with tracked viewing and checkpoint questions.';
$string['modulenameplural'] = 'Video Checkpoints';
$string['name'] = 'Name';
$string['nodrafts'] = 'There are no unpublished AI question drafts.';
$string['numberofquestions'] = 'Number of questions';
$string['optiona'] = 'Option A';
$string['optionb'] = 'Option B';
$string['optionc'] = 'Option C';
$string['optiond'] = 'Option D';
$string['optiongap'] = 'Answer options must be filled in order without empty fields between them.';
$string['pluginadministration'] = 'Video Checkpoint administration';
$string['pluginname'] = 'Video Checkpoint';
$string['privacy:metadata:answers'] = 'Answers submitted at video checkpoints.';
$string['privacy:metadata:answers:answer'] = 'The selected answer and whether it was correct.';
$string['privacy:metadata:external:ai'] = 'When a teacher requests AI question generation, the entered transcript and instructions are sent through Moodle’s configured AI provider.';
$string['privacy:metadata:external:vimeo'] = 'If Vimeo is selected, the browser connects directly to Vimeo and Vimeo may process technical and usage data.';
$string['privacy:metadata:progress'] = 'Viewing progress stored for completion tracking.';
$string['privacy:metadata:progress:activity'] = 'The interactive video activity.';
$string['privacy:metadata:progress:userid'] = 'The user whose progress is stored.';
$string['privacy:metadata:progress:watched'] = 'Observed playback progress.';
$string['progress'] = 'Progress';
$string['publish'] = 'Publish';
$string['questionrequired'] = 'Answer this question correctly to continue.';
$string['questiontext'] = 'Question';
$string['requiredquestion'] = 'Required for completion';
$string['sourceupload'] = 'Upload to Moodle';
$string['sourcevimeo'] = 'Vimeo';
$string['transcript'] = 'Transcript';
$string['transcript_help'] = 'Optional transcript used later for AI-assisted question generation.';
$string['videocheckpoint:addinstance'] = 'Add an interactive video';
$string['videocheckpoint:managequestions'] = 'Manage checkpoint questions';
$string['videocheckpoint:view'] = 'View interactive video';
$string['videocheckpoint:viewreports'] = 'View learner progress reports';
$string['videofile'] = 'Video file';
$string['videofile_help'] = 'Upload one browser-compatible MP4 video.';
$string['videosettings'] = 'Video source';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Vimeo URL';
$string['videourl_help'] = 'Use a Vimeo video URL. Private videos must allow embedding on this Moodle domain.';
$string['watchedpercent'] = '{$a}% watched';
