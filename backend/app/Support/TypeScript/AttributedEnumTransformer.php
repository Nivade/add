<?php

declare(strict_types=1);

namespace App\Support\TypeScript;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;

/**
 * EnumTransformer publishes every enum it is handed, which made the wire surface
 * opt-out. This makes #[TypeScript] the single mechanism for enums and classes alike.
 */
final class AttributedEnumTransformer extends EnumTransformer
{
    public function transform(PhpClassNode $phpClassNode, TransformationContext $context): Transformed|Untransformable
    {
        if (count($phpClassNode->getAttributes(TypeScript::class)) === 0) {
            return Untransformable::create();
        }

        return parent::transform($phpClassNode, $context);
    }
}
