<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\MemoryCacheStorage;
use Rector\CodeQuality\Rector\Attribute\ExplicitAttributeNamedArgsRector;
use Rector\CodeQuality\Rector\CallLike\AddNameToBooleanArgumentRector;
use Rector\CodeQuality\Rector\CallLike\AddNameToNullArgumentRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\FunctionLike\NarrowWideUnionReturnTypeRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\MethodCall\SpatieEnumMethodCallToEnumConstRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\ReplaceTestAnnotationWithPrefixedFunctionRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\FlipAssertRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin',
    ])
    ->withCache(cacheClass: MemoryCacheStorage::class)
    ->withComposerBased()
    ->withImportNames()
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        namedArgs: true,
        instanceOf: true,
        if: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
    )
    ->reportUnusedSkips()
    ->withSkip([
        // Nullable accessors are a design commitment, not an oversight: organization
        // numbers have no gender and no birth date, so narrowing a return type from
        // the current Swedish-only body would break the API when Plan 3 lands.
        NarrowWideUnionReturnTypeRector::class,
        // `! $x instanceof Foo` reads worse than `$x === null` and fully-qualifies the
        // class inside its own file. Null checks stay null checks.
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Rewrites assertNull() on a nullable object to assertNotInstanceOf(),
        // which passes for any non-instance rather than for null specifically.
        // Equivalent given the return type, but weaker as a statement, and
        // several of these assertions exist precisely to pin "null" as the
        // documented answer — an ambiguous detect() resolving to no single
        // number, for one.
        AssertEmptyNullableObjectToAssertInstanceofRector::class,
        // Assumes a class constant is the expected value. In SpecVersionTest the
        // constant is what's under test and `spec/VERSION` is the authority, so
        // flipping it makes a failure name the wrong file as the source of truth.
        FlipAssertRector::class,
        FinalizeTestCaseClassRector::class,
        ClosureToArrowFunctionRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
        SpatieEnumMethodCallToEnumConstRector::class,
        YieldDataProviderRector::class,
        ReplaceTestAnnotationWithPrefixedFunctionRector::class,
        AddNameToBooleanArgumentRector::class,
        ExplicitAttributeNamedArgsRector::class,
        AddNameToNullArgumentRector::class,
        ObjectExplicitBoolCompareRector::class,
    ]);
