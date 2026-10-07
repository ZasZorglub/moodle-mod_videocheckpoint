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
 * Tests playback interval calculations.
 */
#[CoversClass(\mod_videocheckpoint\local\progress::class)]
final class progress_test extends \advanced_testcase {
    public function test_union_seconds_merges_overlapping_segments(): void {
        $segments = [[0, 10], [8, 20], [30, 40], [39, 45]];
        $this->assertSame(35, \mod_videocheckpoint\local\progress::union_seconds($segments));
    }

    public function test_union_seconds_does_not_double_count_adjacent_segments(): void {
        $segments = [[0, 8], [8, 16], [16, 24]];
        $this->assertSame(24, \mod_videocheckpoint\local\progress::union_seconds($segments));
    }
}
