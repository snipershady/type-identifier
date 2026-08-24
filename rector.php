<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
                ->withPaths([
                    __DIR__ . '/src',
                    __DIR__ . '/tests',
                ])
                ->withCache(__DIR__ . '/.rector.cache')
                ->withSkip([
                    NewlineAfterStatementRector::class,
                    RenamePropertyToMatchTypeRector::class,
                    RenameVariableToMatchMethodCallReturnTypeRector::class,
                    RenameVariableToMatchNewTypeRector::class,
                    TypedPropertyFromAssignsRector::class,
                    TypedPropertyFromStrictSetUpRector::class,
                    Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector::class,
                ])
                ->withPreparedSets(
                    deadCode: true,
                    codeQuality: true,
                    codingStyle: true,
                    typeDeclarations: true,
                    typeDeclarationDocblocks: true,
                    privatization: true,
                    naming: true,
                    namedArgs: true,
                    instanceOf: true,
                    earlyReturn: true,
                    phpunitCodeQuality: true,
                    phpunitNarrowAsserts: true,
                    phpunitMockToStub: true,
                )
                ->withAttributesSets(phpunit: true)
                ->withComposerBased(phpunit: true)
                ->withPhpVersion(PhpVersion::PHP_56)
                ->withSets(
                    [
                        LevelSetList::UP_TO_PHP_56,
                    ]
                )
;
