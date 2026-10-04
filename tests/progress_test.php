<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo;

final class progress_test extends \advanced_testcase {
    public function test_union_seconds_merges_overlapping_segments(): void {
        $segments = [[0, 10], [8, 20], [30, 40], [39, 45]];
        $this->assertSame(35, \mod_interactivevideo\local\progress::union_seconds($segments));
    }

    public function test_union_seconds_does_not_double_count_adjacent_segments(): void {
        $segments = [[0, 8], [8, 16], [16, 24]];
        $this->assertSame(24, \mod_interactivevideo\local\progress::union_seconds($segments));
    }
}
