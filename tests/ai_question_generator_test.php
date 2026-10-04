<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo;

final class ai_question_generator_test extends \advanced_testcase {
    public function test_parse_accepts_valid_json(): void {
        $json = json_encode([[
            'time' => 75,
            'question' => 'What is the central point?',
            'options' => ['First', 'Second', 'Third'],
            'correct' => 1,
            'feedback' => 'The second option matches the transcript.',
        ]], JSON_THROW_ON_ERROR);
        $result = \mod_interactivevideo\local\ai_question_generator::parse($json, 4);
        $this->assertCount(1, $result);
        $this->assertSame(75, $result[0]['time']);
        $this->assertSame(1, $result[0]['correct']);
    }

    public function test_parse_rejects_invalid_questions(): void {
        $this->expectException(\moodle_exception::class);
        \mod_interactivevideo\local\ai_question_generator::parse('[{"question":"Missing options"}]', 4);
    }
}
