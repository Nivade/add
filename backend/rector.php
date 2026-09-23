<?php

declare(strict_types=1);

use Nvade\Devtools\Rector\Preset;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Rector\If_\ThrowIfRector;
use RectorLaravel\Set\LaravelSetList;

return Preset::laravel(__DIR__)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // A byte change here changes AiRequest::cacheKey(), so every cached answer
    // silently misses and the fixtures stop matching.
    ->withSkipPath(__DIR__.'/app/Support/Ai/Prompts.php')
    // Keeps partially qualified names like Watchers\CacheWatcher; drop once devtools stops enabling it.
    ->withImportNames(importNames: false, importDocBlockNames: false, importShortClasses: false, removeUnusedImports: false)
    ->withSets([
        // `.ai/rules/general.md` prefers attributes over class properties; this
        // set is what enforces it instead of leaving it to goodwill.
        LaravelSetList::LARAVEL_130,
    ])
    ->withSkip([
        // Collapses guard clauses into throw_if()/throw_unless(), which build the
        // exception whether or not it throws, and the preset already skips the
        // rule that would normalise the result because it drops `previous:`.
        ThrowIfRector::class,
        // Passes the caught exception's code as the new exception's code on calls
        // that already forward `previous:`.
        ThrowWithPreviousExceptionRector::class,
        // One container helper. `app()` is already the one in use.
        AppToResolveRector::class,
    ]);
