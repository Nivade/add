<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\TypeScript\AttributedEnumTransformer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;

/** Generates packages/shared/src/generated.ts. Never hand-edit that file. */
final class TypeScriptTransformerServiceProvider extends BaseProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(AttributedEnumTransformer::class)
            ->transformDirectories(app_path('Data'), app_path('Enums'))
            ->replaceType(CarbonImmutable::class, 'string')
            ->replaceType(CarbonInterface::class, 'string')
            ->replaceType(DateTimeInterface::class, 'string')
            ->outputDirectory(base_path('../packages/shared/src'))
            ->writer(new FlatModuleWriter('generated.ts'));
    }
}
