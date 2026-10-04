<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo;

final class video_test extends \advanced_testcase {
    public function test_vimeo_id_accepts_supported_urls(): void {
        $this->assertSame('123456789', \mod_interactivevideo\local\video::vimeo_id('https://vimeo.com/123456789'));
        $this->assertSame('123456789', \mod_interactivevideo\local\video::vimeo_id('https://player.vimeo.com/video/123456789'));
    }

    public function test_vimeo_id_rejects_other_hosts(): void {
        $this->assertNull(\mod_interactivevideo\local\video::vimeo_id('https://example.com/123456789'));
    }

    public function test_vimeo_embed_url_preserves_unlisted_hash(): void {
        $this->assertSame(
            'https://player.vimeo.com/video/123456789?h=abc123&dnt=1',
            \mod_interactivevideo\local\video::vimeo_embed_url('https://vimeo.com/123456789/abc123')
        );
    }
}
