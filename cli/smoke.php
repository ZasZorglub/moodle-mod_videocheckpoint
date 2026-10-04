<?php
// This file is part of Moodle - https://moodle.org/

define('CLI_SCRIPT', true);

$configpath = dirname(__DIR__, 4) . '/config.php';
if (!is_file($configpath)) {
    $configpath = dirname(__DIR__, 3) . '/config.php';
}
require($configpath);

$plugin = core_plugin_manager::instance()->get_plugin_info('mod_interactivevideo');
if (!$plugin || !$plugin->versiondb) {
    throw new moodle_exception('Plugin is not installed.');
}

foreach (['interactivevideo', 'interactivevideo_cues', 'interactivevideo_progress',
        'interactivevideo_segments', 'interactivevideo_answers'] as $tablename) {
    if (!$DB->get_manager()->table_exists(new xmldb_table($tablename))) {
        throw new moodle_exception('Missing table: ' . $tablename);
    }
}

$eventclass = new ReflectionClass(\mod_interactivevideo\event\course_module_viewed::class);
if (!$eventclass->isSubclassOf(\core\event\course_module_viewed::class)) {
    throw new moodle_exception('The course_module_viewed event does not extend the Moodle event base class.');
}
if (!is_subclass_of(\mod_interactivevideo\privacy\provider::class,
        \core_privacy\local\request\plugin\provider::class)) {
    throw new moodle_exception('The privacy provider does not implement the required interface.');
}
if (\mod_interactivevideo\local\progress::union_seconds([[0, 10], [8, 20], [30, 45]]) !== 35) {
    throw new moodle_exception('Progress interval calculation failed.');
}
if (\mod_interactivevideo\local\video::vimeo_embed_url('https://vimeo.com/123456789/abc123')
        !== 'https://player.vimeo.com/video/123456789?h=abc123&dnt=1') {
    throw new moodle_exception('Vimeo URL parsing failed.');
}
$questions = \mod_interactivevideo\local\ai_question_generator::parse(
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
