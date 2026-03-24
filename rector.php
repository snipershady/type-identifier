<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
                ->withPaths([
                    __DIR__.'/src',
                    __DIR__.'/tests',
                ])
                ->withSkip([
                    NewlineAfterStatementRector::class,
                    NewMethodCallWithoutParenthesesRector::class,
                    RenamePropertyToMatchTypeRector::class,
                    RenameVariableToMatchMethodCallReturnTypeRector::class,
                    RenameVariableToMatchNewTypeRector::class,
                    TypedPropertyFromAssignsRector::class,
                    TypedPropertyFromStrictSetUpRector::class,
                    Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector::class,
                ])
                ->withPreparedSets(
                    // deadCode: true,
                    // codeQuality: true,
                    codingStyle: true,
                    naming: true,
                    // privatization: true,
                    // typeDeclarations: true,
                    // rectorPreset: true
                )
                ->withPhpSets(php56: true)
                ->withPhpVersion(PhpVersion::PHP_56)
                ->withAttributesSets(symfony: true, doctrine: true)
                ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
                ->withSets(
                    [
                        LevelSetList::UP_TO_PHP_56,
                    ]
                )
                ->withRules(
                    [
                        // ExplicitNullableParamTypeRector::class,
                        // AddOverrideAttributeToOverriddenMethodsRector::class,
                        // ReturnTypeFromStrictNativeCallRector::class
                    ]
                )
                ->withTypeCoverageLevel(50)
                ->withDeadCodeLevel(50)
                ->withCodeQualityLevel(50)
// ->withCodingStyleLevel(24) // use php-csfix instead
;
