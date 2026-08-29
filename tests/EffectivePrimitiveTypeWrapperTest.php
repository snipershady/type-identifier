<?php

declare(strict_types=1);

namespace TypeIdentifier\Tests;

use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

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

/**
 * Unit tests for the typed convenience wrappers of
 * EffectivePrimitiveTypeIdentifierService.
 *
 * Two families are covered:
 *
 *  - The scalar wrappers getIntValue(), getFloatValue(), getBoolValue() and
 *    getStringValue(), which cast the result of getTypedValue() to a single
 *    primitive type.
 *  - The superglobal wrappers getIntValueFrom*(), getFloatValueFrom*(),
 *    getBoolValueFrom*() and getStringValueFrom*() (From{Post,Get,Cookie,
 *    Server,Env}), which do the same on top of getTypedValueFrom*().
 *
 * The int/float/bool wrappers expose only ($data|$needle, $trim); $forceString
 * and $sanitizeHtml were dropped from their signatures because they are
 * meaningless once the value is cast to a number or a boolean. The string
 * wrappers keep all four parameters. Both facts are asserted here via
 * reflection so a regression on the signatures fails the suite.
 *
 * The superglobal wrappers are exercised through the direct $_* array fallback
 * that readFromInput() uses when filter_input() returns null (the CLI/PHPUnit
 * case).
 *
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeWrapperTest.php
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeWrapperTest.php --colors="auto" --debug
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class EffectivePrimitiveTypeWrapperTest extends AbstractTestCase
{
    /**
     * @var array<string, array<int|string, mixed>>
     */
    private array $superglobalBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->superglobalBackup = [
            'post' => $_POST,
            'get' => $_GET,
            'cookie' => $_COOKIE,
            'server' => $_SERVER,
            'env' => $_ENV,
        ];
    }

    protected function tearDown(): void
    {
        $_POST = $this->superglobalBackup['post'];
        $_GET = $this->superglobalBackup['get'];
        $_COOKIE = $this->superglobalBackup['cookie'];
        $_SERVER = $this->superglobalBackup['server'];
        $_ENV = $this->superglobalBackup['env'];
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Signatures: int/float/bool wrappers drop $forceString and $sanitizeHtml
    // -------------------------------------------------------------------------

    public function testScalarIntFloatBoolWrappersExposeOnlyDataAndTrim(): void
    {
        foreach (['getIntValue', 'getFloatValue', 'getBoolValue'] as $method) {
            $this->assertSame(['data', 'trim'], $this->parameterNames($method), $method);
        }
    }

    public function testSuperglobalIntFloatBoolWrappersExposeOnlyNeedleAndTrim(): void
    {
        foreach (['getIntValueFrom', 'getFloatValueFrom', 'getBoolValueFrom'] as $prefix) {
            foreach (['Post', 'Get', 'Cookie', 'Server', 'Env'] as $source) {
                $method = $prefix . $source;
                $this->assertSame(['needle', 'trim'], $this->parameterNames($method), $method);
            }
        }
    }

    public function testStringWrappersKeepForceStringAndSanitizeHtml(): void
    {
        $this->assertSame(
            ['data', 'trim', 'forceString', 'sanitizeHtml'],
            $this->parameterNames('getStringValue'),
        );

        foreach (['Post', 'Get', 'Cookie', 'Server', 'Env'] as $source) {
            $method = 'getStringValueFrom' . $source;
            $this->assertSame(
                ['needle', 'trim', 'forceString', 'sanitizeHtml'],
                $this->parameterNames($method),
                $method,
            );
        }
    }

    // -------------------------------------------------------------------------
    // getIntValue()
    // -------------------------------------------------------------------------

    public function testGetIntValueFromNumericString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValue('42');
        $this->assertIsInt($result);
        $this->assertSame(42, $result);
    }

    public function testGetIntValueTruncatesFloat(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValue('3.99');
        $this->assertIsInt($result);
        $this->assertSame(3, $result);
    }

    public function testGetIntValueFromNonNumericStringIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValue('snipershady');
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    public function testGetIntValueFromNullIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValue(data: null);
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    public function testGetIntValueFromBoolTrueIsOne(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValue(data: true);
        $this->assertIsInt($result);
        $this->assertSame(1, $result);
    }

    // -------------------------------------------------------------------------
    // getFloatValue()
    // -------------------------------------------------------------------------

    public function testGetFloatValueFromNumericString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValue('3.14');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(3.14, $result, PHP_FLOAT_EPSILON);
    }

    public function testGetFloatValuePromotesInteger(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValue('42');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(42.0, $result, PHP_FLOAT_EPSILON);
    }

    public function testGetFloatValueFromNonNumericStringIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValue('nope');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(0.0, $result, PHP_FLOAT_EPSILON);
    }

    // -------------------------------------------------------------------------
    // getBoolValue()
    // -------------------------------------------------------------------------

    public function testGetBoolValueFromTruthyValues(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertTrue($ept->getBoolValue(data: true));
        $this->assertTrue($ept->getBoolValue(1));
        $this->assertTrue($ept->getBoolValue('1'));
    }

    public function testGetBoolValueFromFalsyValues(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertFalse($ept->getBoolValue(data: false));
        $this->assertFalse($ept->getBoolValue(0));
        $this->assertFalse($ept->getBoolValue('0'));
        $this->assertFalse($ept->getBoolValue(''));
        $this->assertFalse($ept->getBoolValue(data: null));
    }

    /**
     * The literal string "false" is a non-empty string, so it resolves to true.
     */
    public function testGetBoolValueFromLiteralFalseStringIsTrue(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getBoolValue('false');
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // getStringValue()
    // -------------------------------------------------------------------------

    public function testGetStringValueFromInteger(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValue(42);
        $this->assertIsString($result);
        $this->assertSame('42', $result);
    }

    public function testGetStringValueFromNullIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValue(data: null);
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetStringValueFromArrayIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValue(['a', 'b']);
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetStringValueTrimsWhenRequested(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValue('  snipershady  ', trim: true);
        $this->assertIsString($result);
        $this->assertSame('snipershady', $result);
    }

    // -------------------------------------------------------------------------
    // Superglobal wrappers: $_POST
    // -------------------------------------------------------------------------

    public function testGetIntValueFromPost(): void
    {
        $_POST['age'] = '30';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValueFromPost('age');
        $this->assertIsInt($result);
        $this->assertSame(30, $result);
    }

    public function testGetIntValueFromPostMissingKeyIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValueFromPost('missing');
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    public function testGetFloatValueFromPost(): void
    {
        $_POST['ratio'] = '1.5';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValueFromPost('ratio');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(1.5, $result, PHP_FLOAT_EPSILON);
    }

    public function testGetFloatValueFromPostMissingKeyIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValueFromPost('missing');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(0.0, $result, PHP_FLOAT_EPSILON);
    }

    public function testGetStringValueFromPost(): void
    {
        $_POST['name'] = '  Stefano  ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('  Stefano  ', $ept->getStringValueFromPost('name'));
        $this->assertSame('Stefano', $ept->getStringValueFromPost('name', trim: true));
    }

    public function testGetStringValueFromPostMissingKeyIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromPost('missing');
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetStringValueFromPostArrayValueIsEmptyString(): void
    {
        $_POST['tags'] = ['a', 'b'];
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromPost('tags');
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetBoolValueFromPost(): void
    {
        $_POST['accepted'] = '1';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertTrue($ept->getBoolValueFromPost('accepted'));
    }

    public function testGetBoolValueFromPostMissingKeyIsFalse(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getBoolValueFromPost('missing');
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Superglobal wrappers: $_GET / $_COOKIE / $_SERVER / $_ENV wiring
    // -------------------------------------------------------------------------

    public function testGetIntValueFromGet(): void
    {
        $_GET['page'] = '3';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame(3, $ept->getIntValueFromGet('page'));
        $this->assertSame(0, $ept->getIntValueFromGet('missing'));
    }

    public function testGetStringValueFromGet(): void
    {
        $_GET['q'] = '  search  ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('search', $ept->getStringValueFromGet('q', trim: true));
    }

    public function testGetBoolValueFromCookie(): void
    {
        $_COOKIE['consent'] = '0';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertFalse($ept->getBoolValueFromCookie('consent'));

        $_COOKIE['consent'] = '1';
        $this->assertTrue($ept->getBoolValueFromCookie('consent'));
    }

    public function testGetFloatValueFromServer(): void
    {
        $_SERVER['CUSTOM_RATIO'] = '2.5';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValueFromServer('CUSTOM_RATIO');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(2.5, $result, PHP_FLOAT_EPSILON);
    }

    public function testGetIntValueFromEnv(): void
    {
        $_ENV['WORKERS'] = '8';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame(8, $ept->getIntValueFromEnv('WORKERS'));
        $this->assertSame(0, $ept->getIntValueFromEnv('MISSING_WORKERS'));
    }

    public function testGetStringValueFromEnvMissingKeyIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromEnv('MISSING_ENV_KEY');
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    /**
     * @return list<string>
     */
    private function parameterNames(string $method): array
    {
        $reflection = new \ReflectionMethod(EffectivePrimitiveTypeIdentifierService::class, $method);

        return array_map(
            static fn (\ReflectionParameter $reflectionParameter): string => $reflectionParameter->getName(),
            $reflection->getParameters(),
        );
    }
}
