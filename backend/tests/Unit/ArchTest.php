<?php

declare(strict_types=1);

// Narrow a misfire with ->ignoring('App\Some\Namespace') rather than dropping the preset.
arch()->preset()->php();

arch()->preset()->security();

// tests/Pest.php: the framework's own preset minus a rule its ->ignoring() cannot reach.
arch()->preset()->laravelMinusAttributes()->ignoring([
    'App\Notifications\Channels',
    'App\Support\Ai\Exceptions',
    'App\Support\Calendar\Exceptions',
]);
