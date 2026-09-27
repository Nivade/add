<?php

declare(strict_types=1);

// Narrow a misfire with ->ignoring('App\Some\Namespace') rather than dropping the preset.
arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel()->ignoring([
    'App\Notifications\Channels',
    'App\Support\Ai\Exceptions',
    'App\Support\Calendar\Exceptions',
]);

// Fails on a stock skeleton (non-final classes, abstract Controller): opt in once the app is final and strict.
// arch()->preset()->strict();
