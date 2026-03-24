<?php

namespace TypeIdentifier\Tests;

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

use TypeIdentifier\Sanitizer\HtmlSanitizerService;

/**
 * Unit tests for HtmlSanitizerService::sanitize().
 *
 * Verifies that the sanitization pipeline correctly handles:
 *  - Plain strings (must be unchanged)
 *  - Simple HTML tags (inner text preserved, tags stripped)
 *  - XSS payloads (angle brackets and dangerous characters removed)
 *  - Double-encoded HTML entities (decoded then stripped)
 *  - Dangerous characters: &, <, >, ", %, (, ), +
 *  - Empty and whitespace-only strings
 *
 * @example ./vendor/bin/phpunit tests/HtmlSanitizerServiceTest.php
 * @example ./vendor/bin/phpunit tests/HtmlSanitizerServiceTest.php --colors="auto" --debug
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
class HtmlSanitizerServiceTest extends AbstractTestCase
{
    /** @var HtmlSanitizerService */
    private $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizerService();
    }

    // -------------------------------------------------------------------------
    // Plain strings — must be unchanged
    // -------------------------------------------------------------------------

    public function testPlainStringIsUnchanged(): void
    {
        $value = 'Hello World';
        $this->assertSame($value, $this->sanitizer->sanitize($value));
    }

    public function testEmptyStringIsUnchanged(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(''));
    }

    public function testStringWithSpacesIsUnchanged(): void
    {
        $value = '   ';
        $this->assertSame($value, $this->sanitizer->sanitize($value));
    }

    public function testStringWithApostropheIsUnchanged(): void
    {
        $value = "C'era una volta, cappuccetto rosso.";
        $this->assertSame($value, $this->sanitizer->sanitize($value));
    }

    // -------------------------------------------------------------------------
    // HTML tags — tags stripped, inner text preserved
    // -------------------------------------------------------------------------

    public function testSimpleTagStripped(): void
    {
        $this->assertSame('Hello', $this->sanitizer->sanitize('<b>Hello</b>'));
    }

    public function testNestedTagsStripped(): void
    {
        $this->assertSame('Ciao Stefano! alertXSS; ©', $this->sanitizer->sanitize('<p>Ciao <b>Stefano</b>! <script>alert("XSS");</script> &copy;</p>'));
    }

    public function testStrongTagStripped(): void
    {
        $this->assertSame('word', $this->sanitizer->sanitize('<strong>word</strong>'));
    }

    // -------------------------------------------------------------------------
    // XSS payloads
    // -------------------------------------------------------------------------

    public function testSvgXssPayload(): void
    {
        $value = 'asd"><Svg Only=1 OnLoad=confirm(document.cookie)>';
        $result = $this->sanitizer->sanitize($value);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
        $this->assertSame('asd', $result);
    }

    public function testSvgOnloadPayload(): void
    {
        $value = '><svg/onload=confirm(1)';
        $result = $this->sanitizer->sanitize($value);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
    }

    // -------------------------------------------------------------------------
    // Double-encoded payloads — entities decoded before tag stripping
    // -------------------------------------------------------------------------

    /**
     * &lt;script&gt;alert(1)&lt;/script&gt; must be decoded to <script>alert(1)</script>
     * and then fully stripped, leaving only 'alert1'.
     */
    public function testDoubleEncodedScriptTagStripped(): void
    {
        $value = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $result = $this->sanitizer->sanitize($value);
        $this->assertSame('alert1', $result);
        $this->assertStringNotContainsString('script', $result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
    }

    // -------------------------------------------------------------------------
    // Individual dangerous characters
    // -------------------------------------------------------------------------

    public function testAmpersandRemoved(): void
    {
        $this->assertSame('AB', $this->sanitizer->sanitize('A&B'));
    }

    public function testPercentRemoved(): void
    {
        $this->assertSame('50 off', $this->sanitizer->sanitize('50% off'));
    }

    public function testPlusRemoved(): void
    {
        $this->assertSame('AB', $this->sanitizer->sanitize('A+B'));
    }

    public function testDoubleQuoteRemoved(): void
    {
        $this->assertSame('hello', $this->sanitizer->sanitize('"hello"'));
    }

    public function testParenthesesRemoved(): void
    {
        $this->assertSame('alert1', $this->sanitizer->sanitize('alert(1)'));
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function testReturnsString(): void
    {
        $this->assertIsString($this->sanitizer->sanitize('any value'));
    }

    public function testReturnsStringForEmptyInput(): void
    {
        $this->assertIsString($this->sanitizer->sanitize(''));
    }
}
