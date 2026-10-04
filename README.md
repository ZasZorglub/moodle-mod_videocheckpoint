# Interactive video for Moodle

`mod_interactivevideo` is a Moodle 5.2 activity module for uploaded MP4 and Vimeo video with tracked viewing,
mandatory checkpoint questions, grades, and custom activity completion.

## Current vertical slice

- Upload one MP4 to Moodle or provide a Vimeo URL.
- Configure multiple-choice checkpoints at specific timestamps.
- Pause playback and require correct answers for mandatory checkpoints.
- Track unique watched intervals on the server instead of trusting the furthest playback position.
- Update Moodle custom completion and grade data.
- Export and delete learner data through Moodle's Privacy API.
- Back up and restore activity settings, questions, and uploaded video.

AI-assisted question generation uses Moodle's configured AI provider and stores generated questions as unpublished
drafts. A teacher must review and publish each draft. Moodle App offline support remains a later slice.

## Installation

Copy this directory to `mod/interactivevideo`, visit Site administration > Notifications, and complete the Moodle
database upgrade. Run the Moodle JavaScript build before packaging a release so `amd/build/player.min.js` is present.

## Verification

Run Moodle PHPUnit tests for `mod_interactivevideo`, Moodle Plugin CI, and an end-to-end test with both an uploaded MP4
and a Vimeo video before release.
