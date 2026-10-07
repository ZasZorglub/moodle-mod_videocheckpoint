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

namespace mod_videocheckpoint\local;

/**
 * Vimeo URL validation and embed URL helpers.
 */
final class video {
    /**
     * Extracts a numeric Vimeo video ID.
     *
     * @param string $url Vimeo URL.
     * @return string|null Vimeo ID or null for unsupported URLs.
     */
    public static function vimeo_id(string $url): ?string {
        if (
            !preg_match(
                '~^https?://(?:(?:www|player)\.)?vimeo\.com/(?:video/)?([0-9]+)(?:[/?#].*)?$~i',
                trim($url),
                $matches
            )
        ) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Creates a privacy-enhanced Vimeo embed URL.
     *
     * @param string $url Vimeo URL.
     * @return string|null Embed URL or null for unsupported URLs.
     */
    public static function vimeo_embed_url(string $url): ?string {
        $id = self::vimeo_id($url);
        if (!$id) {
            return null;
        }
        $parts = parse_url(trim($url));
        $path = trim((string)($parts['path'] ?? ''), '/');
        $segments = explode('/', $path);
        if (($segments[0] ?? '') === 'video') {
            array_shift($segments);
        }
        array_shift($segments);
        $hash = $segments[0] ?? '';
        parse_str((string)($parts['query'] ?? ''), $query);
        $hash = (string)($query['h'] ?? $hash);
        $params = ['dnt' => 1];
        if (preg_match('/^[a-z0-9]+$/i', $hash)) {
            $params = ['h' => $hash] + $params;
        }
        return 'https://player.vimeo.com/video/' . $id . '?' . http_build_query($params);
    }
}
