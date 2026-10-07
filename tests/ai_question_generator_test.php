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

namespace mod_videocheckpoint;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests validation of AI-generated question data.
 */
#[CoversClass(\mod_videocheckpoint\local\ai_question_generator::class)]
final class ai_question_generator_test extends \advanced_testcase {
    public function test_parse_accepts_valid_json(): void {
        $json = json_encode([[
            'time' => 75,
            'question' => 'What is the central point?',
            'options' => ['First', 'Second', 'Third'],
            'correct' => 1,
            'feedback' => 'The second option matches the transcript.',
        ], ], JSON_THROW_ON_ERROR);
        $result = \mod_videocheckpoint\local\ai_question_generator::parse($json, 4);
        $this->assertCount(1, $result);
        $this->assertSame(75, $result[0]['time']);
        $this->assertSame(1, $result[0]['correct']);
    }

    public function test_parse_rejects_invalid_questions(): void {
        $this->expectException(\moodle_exception::class);
        \mod_videocheckpoint\local\ai_question_generator::parse('[{"question":"Missing options"}]', 4);
    }
}
