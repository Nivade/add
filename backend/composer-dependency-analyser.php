<?php

declare(strict_types=1);

use Nvade\Devtools\Dependencies\Preset;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

// bootstrap/providers.php registers both only when their class exists, so --no-dev still boots.
return Preset::laravel(__DIR__)
    ->ignoreErrorsOnPackageAndPath('laravel/telescope', __DIR__.'/bootstrap/providers.php', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackageAndPath('spatie/laravel-typescript-transformer', __DIR__.'/bootstrap/providers.php', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackageAndPath('spatie/laravel-typescript-transformer', __DIR__.'/app/Providers/TypeScriptTransformerServiceProvider.php', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    // Its #[TypeScript] attributes are never instantiated at runtime, so production code only names them.
    ->ignoreErrorsOnPackage('spatie/typescript-transformer', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
