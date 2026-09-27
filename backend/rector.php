<?php

declare(strict_types=1);

use Nvade\Devtools\Rector\Preset;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Rector\If_\ThrowIfRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;

return Preset::laravel(__DIR__)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // A byte change here changes AiRequest::cacheKey(), so every cached answer misses and the fixtures stop matching.
    ->withSkipPath(__DIR__.'/app/Support/Ai/Prompts.php')
    // Keeps partially qualified names like Watchers\CacheWatcher; drop once devtools stops enabling it.
    ->withImportNames(importNames: false, importDocBlockNames: false, importShortClasses: false, removeUnusedImports: false)
    // Rector's cache survives config and version changes, so key it on both.
    ->withCache(cacheDirectory: __DIR__.'/storage/rector/'.hash('xxh3', hash_file('xxh3', __FILE__).hash_file('xxh3', __DIR__.'/composer.lock')))
    ->withSkip([
        // throw_if() builds the exception whether or not it throws.
        ThrowIfRector::class,
        // Passes the caught exception's code on calls that already forward `previous:`.
        ThrowWithPreviousExceptionRector::class,
        // `app()` is the one container helper in use.
        AppToResolveRector::class,
        // Larastan types `datetime` casts as Illuminate\Support\Carbon, so Date::now() fails stan on assignment.
        CarbonToDateFacadeRector::class,
        // Same Larastan gap: asserts Illuminate\Support\Carbon where the value is a CarbonImmutable.
        AssertEmptyNullableObjectToAssertInstanceofRector::class,
        // Getters on Candidate hide the model's shape from its own methods too.
        PrivatizeLocalGetterToPropertyRector::class,
    ])
    // Naming and namedArgs stay off: they rename deliberate domain vocabulary and name every framework argument.
    ->withPreparedSets(
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        instanceOf: true,
        if: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
    )
    ->withAttributesSets();
