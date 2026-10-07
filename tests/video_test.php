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
 * Tests Vimeo URL handling.
 */
#[CoversClass(\mod_videocheckpoint\local\video::class)]
final class video_test extends \advanced_testcase {
    public function test_vimeo_id_accepts_supported_urls(): void {
        $this->assertSame('123456789', \mod_videocheckpoint\local\video::vimeo_id('https://vimeo.com/123456789'));
        $this->assertSame('123456789', \mod_videocheckpoint\local\video::vimeo_id('https://player.vimeo.com/video/123456789'));
    }

    public function test_vimeo_id_rejects_other_hosts(): void {
        $this->assertNull(\mod_videocheckpoint\local\video::vimeo_id('https://example.com/123456789'));
    }

    public function test_vimeo_embed_url_preserves_unlisted_hash(): void {
        $this->assertSame(
            'https://player.vimeo.com/video/123456789?h=abc123&dnt=1',
            \mod_videocheckpoint\local\video::vimeo_embed_url('https://vimeo.com/123456789/abc123')
        );
    }
}
