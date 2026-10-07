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

define('CLI_SCRIPT', true);

require(dirname(__DIR__, 4) . '/config.php');

$plugin = core_plugin_manager::instance()->get_plugin_info('mod_videocheckpoint');
if (!$plugin || !$plugin->versiondb) {
    throw new moodle_exception('Plugin is not installed.');
}

foreach (
    ['videocheckpoint', 'videocheckpoint_cues', 'videocheckpoint_progress',
        'videocheckpoint_segments', 'videocheckpoint_answers', ] as $tablename
) {
    if (!$DB->get_manager()->table_exists(new xmldb_table($tablename))) {
        throw new moodle_exception('Missing table: ' . $tablename);
    }
}

$eventclass = new ReflectionClass(\mod_videocheckpoint\event\course_module_viewed::class);
if (!$eventclass->isSubclassOf(\core\event\course_module_viewed::class)) {
    throw new moodle_exception('The course_module_viewed event does not extend the Moodle event base class.');
}
if (
    !is_subclass_of(
        \mod_videocheckpoint\privacy\provider::class,
        \core_privacy\local\request\plugin\provider::class
    )
) {
    throw new moodle_exception('The privacy provider does not implement the required interface.');
}
if (\mod_videocheckpoint\local\progress::union_seconds([[0, 10], [8, 20], [30, 45]]) !== 35) {
    throw new moodle_exception('Progress interval calculation failed.');
}
if (
    \mod_videocheckpoint\local\video::vimeo_embed_url('https://vimeo.com/123456789/abc123')
        !== 'https://player.vimeo.com/video/123456789?h=abc123&dnt=1'
) {
    throw new moodle_exception('Vimeo URL parsing failed.');
}
$questions = \mod_videocheckpoint\local\ai_question_generator::parse(
    '[{"time":60,"question":"Q?","options":["A","B"],"correct":1,"feedback":"F"}]',
    4,
);
if (count($questions) !== 1) {
    throw new moodle_exception('AI response validation failed.');
}

$manager = \core\di::get(\core_ai\manager::class);
$providers = $manager->get_providers_for_actions([\core_ai\aiactions\generate_text::class], true);
$providercount = count($providers[\core_ai\aiactions\generate_text::class]);

echo "PLUGIN_RUNTIME_SMOKE_PASS versiondb={$plugin->versiondb} ai_providers={$providercount}" . PHP_EOL;
