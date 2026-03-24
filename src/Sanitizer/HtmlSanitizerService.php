<?php

/*
 * Copyright (C) 2025  Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301 USA.
 */

namespace TypeIdentifier\Sanitizer;

/**
 * Default HTML and XSS sanitizer for plain string values.
 *
 * Processing pipeline (applied in order):
 *  1. FILTER_UNSAFE_RAW with FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH |
 *     FILTER_FLAG_STRIP_BACKTICK — removes control bytes, high bytes and backticks.
 *  2. html_entity_decode() — converts HTML entities to their UTF-8 characters
 *     (e.g. &amp;lt;script&amp;gt; → &lt;script&gt; → decoded before tag stripping
 *     so that double-encoded payloads are also neutralised).
 *  3. strip_tags() — removes all HTML/XML tags from the decoded string.
 *  4. preg_replace() — removes the characters &, <, >, ", %, (, ), +.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class HtmlSanitizerService implements HtmlSanitizerServiceInterface
{
    /**
     * Characters stripped by the final preg_replace pass.
     *
     * @var array<string>
     */
    private static $dangerousPatterns = [
        '/\&/',
        '/</',
        '/>/',
        '/"/',
        '/%/',
        '/\(/',
        '/\)/',
        '/\+/',
    ];

    /**
     * {@inheritdoc}
     *
     * Strips HTML tags and dangerous characters from a raw string.
     *
     * The pipeline is intentionally ordered so that double-encoded payloads
     * (e.g. &amp;lt;script&amp;gt;) are decoded before tag stripping, preventing
     * them from surviving the sanitization process.
     *
     * @param string $string raw string potentially containing HTML or XSS payloads
     *
     * @return string sanitized plain-text string
     */
    #[\Override]
    public function sanitize($string)
    {
        $stringFiltered = (string) filter_var(
            $string,
            FILTER_UNSAFE_RAW,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
        );
        $stringDecoded = html_entity_decode($stringFiltered);
        $stringStripped = strip_tags($stringDecoded);

        return (string) preg_replace(self::$dangerousPatterns, '', $stringStripped);
    }
}
