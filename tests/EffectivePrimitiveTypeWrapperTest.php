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
 * Three families are covered:
 *
 *  - The scalar wrappers getIntValue(), getFloatValue(), getBoolValue() and
 *    getStringValue(), which cast the result of getTypedValue() to a single
 *    primitive type.
 *  - The superglobal wrappers getIntValueFrom*(), getFloatValueFrom*(),
 *    getBoolValueFrom*() and getStringValueFrom*() (From{Post,Get,Cookie,
 *    Server,Env}), which do the same on top of getTypedValueFrom*().
 *  - The array wrappers getIntValueFromArray(), getFloatValueFromArray(),
 *    getBoolValueFromArray() and getStringValueFromArray(), the counterparts of
 *    the superglobal family for an arbitrary array source. They carry the
 *    ($needle, $array) pair of getTypedValueFromArray() instead of a lone
 *    $needle, and never return null: a null $array or an absent key resolves to
 *    the zero value of the target type.
 *
 * The int/float/bool wrappers expose only ($data|$needle, $trim); $forceString
 * and $sanitizeHtml were dropped from their signatures because they are
 * meaningless once the value is cast to a number or a boolean. The string
 * wrappers keep all four parameters. Both facts are asserted here via
 * reflection so a regression on the signatures fails the suite.
 *
 * $maxDepth is deliberately absent from all of them, for the same reason: these
 * wrappers collapse an array to a scalar (0/1, 0.0/1.0, false/true, ""), so the
 * nesting they walk is never returned and there is nothing for the caller to
 * tune. Only the seven methods whose return type includes `array` take it. They
 * are still protected by the DEFAULT_MAX_DEPTH ceiling — see MaxDepthTest.
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

    public function testArrayIntFloatBoolWrappersExposeNeedleArrayAndTrim(): void
    {
        foreach (['getIntValueFromArray', 'getFloatValueFromArray', 'getBoolValueFromArray'] as $method) {
            $this->assertSame(['needle', 'array', 'trim'], $this->parameterNames($method), $method);
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

        $this->assertSame(
            ['needle', 'array', 'trim', 'forceString', 'sanitizeHtml'],
            $this->parameterNames('getStringValueFromArray'),
        );
    }

    /**
     * The array family must mirror the superglobal one method for method: the
     * asymmetry of having getTypedValueFromArray() without typed counterparts
     * is what these accessors exist to close.
     */
    public function testArrayFamilyMirrorsTheSuperglobalFamily(): void
    {
        foreach (['getIntValue', 'getFloatValue', 'getBoolValue', 'getStringValue'] as $prefix) {
            $this->assertTrue(
                method_exists(EffectivePrimitiveTypeIdentifierService::class, $prefix . 'FromArray'),
                $prefix . 'FromArray() must exist alongside ' . $prefix . 'FromPost().',
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

    public function testGetFloatValueFromGet(): void
    {
        $_GET['ratio'] = '2.75';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValueFromGet('ratio');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(2.75, $result, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $ept->getFloatValueFromGet('missing'), PHP_FLOAT_EPSILON);
    }

    public function testGetBoolValueFromGet(): void
    {
        $_GET['flag'] = '1';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertTrue($ept->getBoolValueFromGet('flag'));

        $_GET['flag'] = '0';
        $this->assertFalse($ept->getBoolValueFromGet('flag'));
        $this->assertFalse($ept->getBoolValueFromGet('missing'));
    }

    public function testGetStringValueFromGetMissingKeyIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromGet('missing');
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetIntValueFromServer(): void
    {
        $_SERVER['CUSTOM_PORT'] = '8080';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValueFromServer('CUSTOM_PORT');
        $this->assertIsInt($result);
        $this->assertSame(8080, $result);
        $this->assertSame(0, $ept->getIntValueFromServer('CUSTOM_MISSING'));
    }

    public function testGetStringValueFromServer(): void
    {
        $_SERVER['CUSTOM_AGENT'] = '  typeidentifier  ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('  typeidentifier  ', $ept->getStringValueFromServer('CUSTOM_AGENT'));
        $this->assertSame('typeidentifier', $ept->getStringValueFromServer('CUSTOM_AGENT', trim: true));
        $this->assertSame('', $ept->getStringValueFromServer('CUSTOM_MISSING'));
    }

    public function testGetBoolValueFromServer(): void
    {
        $_SERVER['CUSTOM_FLAG'] = '1';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertTrue($ept->getBoolValueFromServer('CUSTOM_FLAG'));

        $_SERVER['CUSTOM_FLAG'] = '0';
        $this->assertFalse($ept->getBoolValueFromServer('CUSTOM_FLAG'));
        $this->assertFalse($ept->getBoolValueFromServer('CUSTOM_MISSING'));
    }

    public function testGetIntValueFromCookie(): void
    {
        $_COOKIE['session_ttl'] = '3600';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getIntValueFromCookie('session_ttl');
        $this->assertIsInt($result);
        $this->assertSame(3600, $result);
        $this->assertSame(0, $ept->getIntValueFromCookie('missing'));
    }

    public function testGetFloatValueFromCookie(): void
    {
        $_COOKIE['zoom'] = '1.25';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getFloatValueFromCookie('zoom');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(1.25, $result, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $ept->getFloatValueFromCookie('missing'), PHP_FLOAT_EPSILON);
    }

    public function testGetStringValueFromCookie(): void
    {
        $_COOKIE['locale'] = '  it_IT  ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('  it_IT  ', $ept->getStringValueFromCookie('locale'));
        $this->assertSame('it_IT', $ept->getStringValueFromCookie('locale', trim: true));
        $this->assertSame('', $ept->getStringValueFromCookie('missing'));
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

    // -------------------------------------------------------------------------
    // Array wrappers: getIntValueFromArray() & co.
    // -------------------------------------------------------------------------

    public function testGetIntValueFromArray(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame(30, $ept->getIntValueFromArray('age', ['age' => '30']));
        $this->assertSame(3, $ept->getIntValueFromArray('ratio', ['ratio' => '3.99']));
        $this->assertSame(0, $ept->getIntValueFromArray('name', ['name' => 'snipershady']));
    }

    public function testGetIntValueFromArrayMissingKeyOrNullArrayIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame(0, $ept->getIntValueFromArray('missing', ['other' => 1]));
        $this->assertSame(0, $ept->getIntValueFromArray('any', array: null));
    }

    public function testGetIntValueFromArrayWithIntegerKey(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame(7, $ept->getIntValueFromArray(0, ['7', '8']));
    }

    public function testGetFloatValueFromArray(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertEqualsWithDelta(1.5, $ept->getFloatValueFromArray('ratio', ['ratio' => '1.5']), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(42.0, $ept->getFloatValueFromArray('n', ['n' => '42']), PHP_FLOAT_EPSILON);
    }

    public function testGetFloatValueFromArrayMissingKeyOrNullArrayIsZero(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertEqualsWithDelta(0.0, $ept->getFloatValueFromArray('missing', ['other' => 1.0]), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $ept->getFloatValueFromArray('any', array: null), PHP_FLOAT_EPSILON);
    }

    public function testGetStringValueFromArray(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('  Stefano  ', $ept->getStringValueFromArray('name', ['name' => '  Stefano  ']));
        $this->assertSame('Stefano', $ept->getStringValueFromArray('name', ['name' => '  Stefano  '], trim: true));
    }

    public function testGetStringValueFromArrayKeepsNumericStringWithForceString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('007', $ept->getStringValueFromArray('code', ['code' => '007'], forceString: true));
        $this->assertSame('7', $ept->getStringValueFromArray('code', ['code' => '007']));
    }

    public function testGetStringValueFromArraySanitizesHtml(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromArray('bio', ['bio' => '<b>hi</b>'], sanitizeHtml: true);
        $this->assertSame('hi', $result);
    }

    public function testGetStringValueFromArrayNestedArrayIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getStringValueFromArray('tags', ['tags' => ['a', 'b']]);
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    public function testGetStringValueFromArrayMissingKeyOrNullArrayIsEmptyString(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertSame('', $ept->getStringValueFromArray('missing', ['other' => 'x']));
        $this->assertSame('', $ept->getStringValueFromArray('any', array: null));
    }

    public function testGetBoolValueFromArray(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertTrue($ept->getBoolValueFromArray('accepted', ['accepted' => '1']));
        $this->assertFalse($ept->getBoolValueFromArray('accepted', ['accepted' => '0']));
        $this->assertFalse($ept->getBoolValueFromArray('accepted', ['accepted' => '']));
        $this->assertTrue($ept->getBoolValueFromArray('accepted', ['accepted' => 'false']));
    }

    public function testGetBoolValueFromArrayMissingKeyOrNullArrayIsFalse(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $this->assertFalse($ept->getBoolValueFromArray('missing', ['other' => true]));
        $this->assertFalse($ept->getBoolValueFromArray('any', array: null));
    }

    /**
     * A null value under an existing key must behave like an absent key: the
     * wrappers never return null, they return the zero value of their type.
     */
    public function testArrayWrappersResolveNullValueToTheZeroValue(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $source = ['key' => null];

        $this->assertSame(0, $ept->getIntValueFromArray('key', $source));
        $this->assertEqualsWithDelta(0.0, $ept->getFloatValueFromArray('key', $source), PHP_FLOAT_EPSILON);
        $this->assertSame('', $ept->getStringValueFromArray('key', $source));
        $this->assertFalse($ept->getBoolValueFromArray('key', $source));
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
