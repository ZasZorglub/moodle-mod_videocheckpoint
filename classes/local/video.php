<?php
// This file is part of Moodle - https://moodle.org/

namespace mod_interactivevideo\local;

final class video {
    public static function vimeo_id(string $url): ?string {
        if (!preg_match('~^https?://(?:(?:www|player)\.)?vimeo\.com/(?:video/)?([0-9]+)(?:[/?#].*)?$~i',
                trim($url), $matches)) {
            return null;
        }
        return $matches[1];
    }

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
