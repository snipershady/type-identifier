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
 * Contract for HTML and XSS sanitization of plain string values.
 *
 * Implementations must strip HTML tags and neutralise dangerous characters
 * so that the returned string is safe for storage or output in a plain-text
 * context.  The exact set of characters removed and the processing pipeline
 * are left to the implementing class.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
interface HtmlSanitizerServiceInterface
{
    /**
     * Strips HTML tags and dangerous characters from a raw string.
     *
     * @param string $string raw string potentially containing HTML or XSS payloads
     *
     * @return string sanitized plain-text string
     */
    public function sanitize($string);
}
