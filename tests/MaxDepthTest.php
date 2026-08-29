<?php

declare(strict_types=1);

namespace TypeIdentifier\Tests;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use TypeIdentifier\Exception\MaxDepthExceededException;
use TypeIdentifier\Exception\TypeIdentifierExceptionInterface;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

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
 * Unit tests for the $maxDepth guard of
 * EffectivePrimitiveTypeIdentifierService::getTypedValue().
 *
 * Depth convention under test:
 *  - a scalar / null is depth 0;
 *  - a flat array is depth 1;
 *  - ['a' => ['b' => 1]] is depth 2.
 *
 * Coverage:
 *  - exact boundary (N levels pass with $maxDepth = N, fail with N - 1)
 *  - the DEFAULT_MAX_DEPTH = 64 default, on both sides of the boundary
 *  - self-referencing arrays: a catchable exception instead of the
 *    non-catchable "Allowed memory size exhausted" fatal error
 *  - negative $maxDepth rejected as a programming error
 *  - the guard reaching every entry point that delegates to getTypedValue()
 *    (getTypedValueFromArray(), the superglobal readers, the typed wrappers)
 *
 * @example ./vendor/bin/phpunit tests/MaxDepthTest.php
 * @example ./vendor/bin/phpunit tests/MaxDepthTest.php --colors="auto" --debug
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
#[AllowMockObjectsWithoutExpectations]
final class MaxDepthTest extends AbstractTestCase
{
    private EffectivePrimitiveTypeIdentifierService $ept;

    /**
     * @var array<string, array<int|string, mixed>>
     */
    private array $superglobalBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ept = new EffectivePrimitiveTypeIdentifierService();
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
    // Default value
    // -------------------------------------------------------------------------

    /**
     * The default is 64: PHP's own max_input_nesting_level ceiling, so the guard
     * never rejects a payload the SAPI was willing to build.
     */
    public function testDefaultMaxDepthIsSixtyFour(): void
    {
        $this->assertSame(64, EffectivePrimitiveTypeIdentifierServiceInterface::DEFAULT_MAX_DEPTH);
    }

    /**
     * $maxDepth is the last parameter of getTypedValue() and nothing else moved.
     */
    public function testMaxDepthIsTheLastParameterOfGetTypedValue(): void
    {
        $this->assertSame(
            ['data', 'trim', 'forceString', 'sanitizeHtml', 'maxDepth'],
            $this->parameterNames('getTypedValue'),
        );
    }

    /**
     * Every method that can *return* a nested array exposes $maxDepth, always
     * last, so the caller can tighten or loosen the ceiling on the structure it
     * is about to receive.
     */
    public function testEveryArrayReturningMethodExposesMaxDepthLast(): void
    {
        $methods = [
            'getTypedValue',
            'getTypedValueFromArray',
            'getTypedValueFromPost',
            'getTypedValueFromGet',
            'getTypedValueFromCookie',
            'getTypedValueFromServer',
            'getTypedValueFromEnv',
        ];

        foreach ($methods as $method) {
            $names = $this->parameterNames($method);
            $this->assertSame('maxDepth', end($names), $method . '() must take $maxDepth last.');
        }
    }

    /**
     * The scalar wrappers deliberately do NOT expose it: they collapse an array
     * to 0/1, 0.0/1.0, false/true or "", so the nesting they walk never reaches
     * the caller and there is nothing to tune. Same rationale that kept
     * $forceString and $sanitizeHtml off the int/float/bool wrappers.
     */
    public function testScalarWrappersDoNotExposeMaxDepth(): void
    {
        $methods = ['getIntValue', 'getFloatValue', 'getBoolValue', 'getStringValue'];
        foreach (['Post', 'Get', 'Cookie', 'Server', 'Env', 'Array'] as $source) {
            foreach (['getIntValueFrom', 'getFloatValueFrom', 'getBoolValueFrom', 'getStringValueFrom'] as $prefix) {
                $methods[] = $prefix . $source;
            }
        }

        foreach ($methods as $method) {
            $this->assertNotContains('maxDepth', $this->parameterNames($method), $method . '() must not expose $maxDepth.');
        }
    }

    /**
     * Omitting the argument must behave exactly like passing DEFAULT_MAX_DEPTH.
     */
    public function testDefaultAcceptsExactlySixtyFourLevels(): void
    {
        $result = $this->ept->getTypedValue($this->nest(64));
        $this->assertIsArray($result);
        $this->assertSame('leaf', $this->leafOf($result));
    }

    public function testDefaultRejectsSixtyFiveLevels(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValue($this->nest(65));
    }

    // -------------------------------------------------------------------------
    // Boundary
    // -------------------------------------------------------------------------

    /**
     * A scalar is depth 0, so even $maxDepth = 0 must let it through.
     */
    public function testScalarIsAcceptedWithZeroMaxDepth(): void
    {
        $this->assertSame(42, $this->ept->getTypedValue('42', maxDepth: 0));
        $this->assertSame('x', $this->ept->getTypedValue('x', maxDepth: 0));
        $this->assertNull($this->ept->getTypedValue(data: null, maxDepth: 0));
    }

    /**
     * A flat array is depth 1 and must be rejected at $maxDepth = 0.
     */
    public function testFlatArrayIsRejectedWithZeroMaxDepth(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValue(['a' => 1], maxDepth: 0);
    }

    public function testFlatArrayIsAcceptedWithMaxDepthOne(): void
    {
        $result = $this->ept->getTypedValue(['a' => '1'], maxDepth: 1);
        $this->assertSame(['a' => 1], $result);
    }

    public function testNestedArrayIsRejectedOneLevelBelowItsDepth(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValue(['a' => ['b' => 1]], maxDepth: 1);
    }

    public function testNestedArrayIsAcceptedAtItsExactDepth(): void
    {
        $result = $this->ept->getTypedValue(['a' => ['b' => '1']], maxDepth: 2);
        $this->assertSame(['a' => ['b' => 1]], $result);
    }

    /**
     * The boundary must hold at every level, not only at the documented default.
     */
    public function testBoundaryHoldsAcrossLevels(): void
    {
        foreach ([1, 2, 3, 8, 32] as $levels) {
            $this->assertIsArray(
                $this->ept->getTypedValue($this->nest($levels), maxDepth: $levels),
                sprintf('%d levels must be accepted with maxDepth %d.', $levels, $levels),
            );

            $rejected = false;

            try {
                $this->ept->getTypedValue($this->nest($levels), maxDepth: $levels - 1);
            } catch (MaxDepthExceededException) {
                $rejected = true;
            }

            $this->assertTrue($rejected, sprintf('%d levels must be rejected with maxDepth %d.', $levels, $levels - 1));
        }
    }

    /**
     * Only the nesting of arrays counts: a wide array is not a deep one.
     */
    public function testWideArrayIsNotDeep(): void
    {
        $wide = array_fill(0, 5000, '7');
        $result = $this->ept->getTypedValue($wide, maxDepth: 1);
        $this->assertIsArray($result);
        $this->assertCount(5000, $result);
        $this->assertSame(7, $result[0]);
    }

    /**
     * Sibling branches must not consume each other's depth budget: depth is
     * measured per branch, not as a running total of visited nodes.
     */
    public function testSiblingBranchesDoNotShareTheDepthBudget(): void
    {
        $input = [
            'first' => ['a' => ['b' => '1']],
            'second' => ['c' => ['d' => '2']],
        ];

        $result = $this->ept->getTypedValue($input, maxDepth: 3);
        $this->assertSame(['first' => ['a' => ['b' => 1]], 'second' => ['c' => ['d' => 2]]], $result);
    }

    // -------------------------------------------------------------------------
    // Self-referencing arrays
    // -------------------------------------------------------------------------

    /**
     * A self-referencing array used to recurse until the process died on a
     * non-catchable "Allowed memory size exhausted" fatal error. It must now
     * surface as a catchable exception.
     */
    public function testSelfReferencingArrayThrowsInsteadOfExhaustingMemory(): void
    {
        $array = ['x' => 1];
        $array['self'] = &$array;

        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValue($array);
    }

    /**
     * Two arrays referencing each other form the same unbounded cycle.
     */
    public function testMutuallyReferencingArraysThrow(): void
    {
        $first = ['name' => 'first'];
        $second = ['name' => 'second'];
        $first['peer'] = &$second;
        $second['peer'] = &$first;

        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValue($first);
    }

    // -------------------------------------------------------------------------
    // Exception contract
    // -------------------------------------------------------------------------

    public function testExceptionIsCatchableThroughTheLibraryMarkerInterface(): void
    {
        $this->expectException(TypeIdentifierExceptionInterface::class);
        $this->ept->getTypedValue($this->nest(3), maxDepth: 2);
    }

    public function testExceptionIsARuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->ept->getTypedValue($this->nest(3), maxDepth: 2);
    }

    /**
     * The message must name the limit that was hit, so the caller can act on it.
     */
    public function testExceptionMessageReportsTheConfiguredLimit(): void
    {
        try {
            $this->ept->getTypedValue($this->nest(9), maxDepth: 8);
            self::fail('MaxDepthExceededException was not thrown.');
        } catch (MaxDepthExceededException $maxDepthExceededException) {
            $this->assertStringContainsString('8', $maxDepthExceededException->getMessage());
            $this->assertStringContainsString('nesting depth', $maxDepthExceededException->getMessage());
        }
    }

    /**
     * A negative $maxDepth is a programming error, not hostile data: it must be
     * rejected up front and must NOT be reported as a depth overflow.
     */
    public function testNegativeMaxDepthThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->ept->getTypedValue('scalar', maxDepth: -1);
    }

    public function testNegativeMaxDepthIsRejectedBeforeWalkingTheData(): void
    {
        try {
            $this->ept->getTypedValue(['a' => 1], maxDepth: -5);
            self::fail('InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->assertNotInstanceOf(MaxDepthExceededException::class, $invalidArgumentException);
            $this->assertStringContainsString('-5', $invalidArgumentException->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // The guard reaches every delegating entry point
    // -------------------------------------------------------------------------

    public function testGetTypedValueFromArrayIsGuardedByDefault(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValueFromArray('payload', ['payload' => $this->nest(65)]);
    }

    public function testGetTypedValueFromPostIsGuardedByDefault(): void
    {
        $_POST['payload'] = $this->nest(65);

        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValueFromPost('payload');
    }

    public function testGetStringValueIsGuardedByDefault(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getStringValue($this->nest(65));
    }

    public function testGetBoolValueIsGuardedByDefault(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getBoolValue($this->nest(65));
    }

    /**
     * A payload PHP itself would have built from a request (max_input_nesting_level
     * levels) must still pass through the superglobal readers untouched.
     */
    public function testSuperglobalReaderAcceptsPhpMaxInputNesting(): void
    {
        $_GET['payload'] = $this->nest(EffectivePrimitiveTypeIdentifierServiceInterface::DEFAULT_MAX_DEPTH);

        $result = $this->ept->getTypedValueFromGet('payload');
        $this->assertIsArray($result);
        $this->assertSame('leaf', $this->leafOf($result));
    }

    // -------------------------------------------------------------------------
    // The limit is tunable through every delegating entry point
    // -------------------------------------------------------------------------

    public function testGetTypedValueFromArrayHonoursACustomMaxDepth(): void
    {
        $source = ['payload' => $this->nest(3)];

        $result = $this->ept->getTypedValueFromArray('payload', $source, maxDepth: 3);
        $this->assertIsArray($result);
        $this->assertSame('leaf', $this->leafOf($result));

        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValueFromArray('payload', $source, maxDepth: 2);
    }

    /**
     * A caller may also tighten the ceiling well below the default to reject
     * structures the SAPI would happily have built.
     */
    public function testGetTypedValueFromArrayAcceptsATighterCeilingThanTheDefault(): void
    {
        $this->expectException(MaxDepthExceededException::class);
        $this->ept->getTypedValueFromArray('payload', ['payload' => $this->nest(5)], maxDepth: 1);
    }

    public function testGetTypedValueFromArrayAcceptsALooserCeilingThanTheDefault(): void
    {
        $result = $this->ept->getTypedValueFromArray('payload', ['payload' => $this->nest(120)], maxDepth: 120);
        $this->assertIsArray($result);
        $this->assertSame('leaf', $this->leafOf($result));
    }

    /**
     * All five superglobal readers must forward $maxDepth down to getTypedValue(),
     * in both directions, through the direct $_* fallback used under CLI.
     *
     * Written out call by call rather than through a data provider: a variable
     * method name would hide the very signatures this test exists to pin down.
     */
    public function testSuperglobalReadersAcceptAPayloadAtTheirCustomMaxDepth(): void
    {
        $this->seedEverySuperglobal($this->nest(4));

        $results = [
            'getTypedValueFromPost' => $this->ept->getTypedValueFromPost('payload', maxDepth: 4),
            'getTypedValueFromGet' => $this->ept->getTypedValueFromGet('payload', maxDepth: 4),
            'getTypedValueFromCookie' => $this->ept->getTypedValueFromCookie('payload', maxDepth: 4),
            'getTypedValueFromServer' => $this->ept->getTypedValueFromServer('payload', maxDepth: 4),
            'getTypedValueFromEnv' => $this->ept->getTypedValueFromEnv('payload', maxDepth: 4),
        ];

        foreach ($results as $method => $result) {
            $this->assertIsArray($result, $method . '() must accept 4 levels with maxDepth 4.');
            $this->assertSame('leaf', $this->leafOf($result), $method . '() must preserve the leaf.');
        }
    }

    public function testSuperglobalReadersRejectAPayloadDeeperThanTheirCustomMaxDepth(): void
    {
        $this->seedEverySuperglobal($this->nest(4));

        $this->assertRejects(fn (): mixed => $this->ept->getTypedValueFromPost('payload', maxDepth: 3), 'getTypedValueFromPost');
        $this->assertRejects(fn (): mixed => $this->ept->getTypedValueFromGet('payload', maxDepth: 3), 'getTypedValueFromGet');
        $this->assertRejects(fn (): mixed => $this->ept->getTypedValueFromCookie('payload', maxDepth: 3), 'getTypedValueFromCookie');
        $this->assertRejects(fn (): mixed => $this->ept->getTypedValueFromServer('payload', maxDepth: 3), 'getTypedValueFromServer');
        $this->assertRejects(fn (): mixed => $this->ept->getTypedValueFromEnv('payload', maxDepth: 3), 'getTypedValueFromEnv');
    }

    // -------------------------------------------------------------------------
    // No behavioural regression within the limit
    // -------------------------------------------------------------------------

    /**
     * Inside the allowed depth every other flag must keep working as before.
     */
    public function testFlagsStillApplyToNestedValuesWithinTheLimit(): void
    {
        $input = ['outer' => ['num' => '  42  ', 'html' => ' <b>hi</b> ']];

        $result = $this->ept->getTypedValue($input, trim: true, forceString: true, sanitizeHtml: true, maxDepth: 2);

        $this->assertSame(['outer' => ['num' => '42', 'html' => 'hi']], $result);
    }

    /**
     * Writes $value under the 'payload' key of every superglobal the readers use.
     *
     * @param array<array-key, mixed> $value
     */
    private function seedEverySuperglobal(array $value): void
    {
        $_POST['payload'] = $value;
        $_GET['payload'] = $value;
        $_COOKIE['payload'] = $value;
        $_SERVER['payload'] = $value;
        $_ENV['payload'] = $value;
    }

    /**
     * Asserts that $read() bails out with a MaxDepthExceededException.
     *
     * expectException() cannot be used here because a single test asserts on
     * several readers in a row.
     *
     * @param \Closure(): mixed $read
     */
    private function assertRejects(\Closure $read, string $label): void
    {
        $rejected = false;

        try {
            $read();
        } catch (MaxDepthExceededException) {
            $rejected = true;
        }

        $this->assertTrue($rejected, $label . '() must reject a payload deeper than its $maxDepth.');
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

    /**
     * Builds $levels nested arrays around the string 'leaf'.
     *
     * @param int<1, max> $levels
     *
     * @return array<array-key, mixed>
     */
    private function nest(int $levels): array
    {
        $nested = 'leaf';
        for ($i = 0; $i < $levels; ++$i) {
            $nested = [$nested];
        }

        $this->assertIsArray($nested);

        return $nested;
    }

    /**
     * Walks a structure built by nest() back down to its scalar leaf.
     *
     * @param array<array-key, mixed> $nested
     */
    private function leafOf(array $nested): mixed
    {
        $current = $nested;
        while (is_array($current)) {
            $current = $current[0] ?? null;
        }

        return $current;
    }
}
