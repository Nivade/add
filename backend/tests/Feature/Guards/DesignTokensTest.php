<?php

declare(strict_types=1);

/** @return array<string, string> css variable name without the dashes => hex */
function designTokenColors(string $theme): array
{
    $source = (string) file_get_contents(repoPath('packages/shared/src/tokens.ts'));

    preg_match('/export const '.$theme.'Colors = \{(.*?)\} as const;/s', $source, $block);
    preg_match_all("/^\s*(\w+): '(#[0-9A-Fa-f]{6})',$/m", $block[1] ?? '', $pairs, PREG_SET_ORDER);

    $colors = [];

    foreach ($pairs as [, $key, $hex]) {
        $colors[strtolower((string) preg_replace('/[A-Z]/', '-$0', $key))] = strtoupper($hex);
    }

    return $colors;
}

/** @return array<string, string> css variable name without the dashes => hex */
function designTokenCssColors(string $selector): array
{
    $css = (string) file_get_contents(base_path('resources/css/app.css'));

    preg_match('/^'.preg_quote($selector, '/').' \{(.*?)^\}/ms', $css, $block);
    preg_match_all('/--([a-z-]+):\s*(#[0-9A-Fa-f]{6});/', $block[1] ?? '', $pairs, PREG_SET_ORDER);

    $colors = [];

    foreach ($pairs as [, $name, $hex]) {
        $colors[$name] = strtoupper($hex);
    }

    return $colors;
}

function designTokenLuminance(string $hex): float
{
    $channels = array_map(function (string $pair): float {
        $value = hexdec($pair) / 255;

        return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, str_split(ltrim($hex, '#'), 2));

    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function designTokenContrast(string $foreground, string $background): float
{
    $lighter = max(designTokenLuminance($foreground), designTokenLuminance($background));
    $darker = min(designTokenLuminance($foreground), designTokenLuminance($background));

    return ($lighter + 0.05) / ($darker + 0.05);
}

/** @return list<string> */
function designTokenNames(): array
{
    return ['paper', 'surface', 'ink', 'muted', 'line', 'field', 'now', 'on-now'];
}

// The identity's contrast is a promise to people reading on a bad day.
it('keeps every pair of the palette legible', function (string $theme, string $foreground, string $background, float $minimum): void {
    $colors = designTokenColors($theme);

    expect(designTokenContrast($colors[$foreground], $colors[$background]))
        ->toBeGreaterThanOrEqual($minimum, "{$theme}: {$foreground} on {$background}");
})->with(['light', 'dark'])->with([
    ['ink', 'paper', 7.0],
    ['ink', 'surface', 7.0],
    ['muted', 'paper', 4.5],
    ['muted', 'surface', 4.5],
    ['now', 'paper', 4.5],
    ['now', 'surface', 4.5],
    ['on-now', 'now', 4.5],
    ['field', 'paper', 3.0],
    ['field', 'surface', 3.0],
]);

it('declares every token in the stylesheet with the same hex as the shared tokens', function (string $theme, string $selector): void {
    $tokens = designTokenColors($theme);
    $css = designTokenCssColors($selector);

    expect(array_keys($tokens))->toEqualCanonicalizing(designTokenNames());

    foreach (designTokenNames() as $name) {
        expect($css[$name] ?? null)->toBe($tokens[$name], "{$selector} --{$name}");
    }
})->with([
    ['light', ':root'],
    ['dark', '.dark'],
]);

it('keeps shouting labels and pixel type sizes out of the web components', function (): void {
    $root = base_path('resources/js');
    $skipped = ['components/ui/', 'actions/', 'routes/', 'wayfinder/'];
    $violations = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');

        if (! $file->isFile() || $file->getExtension() !== 'tsx') {
            continue;
        }

        if (array_filter($skipped, fn (string $prefix): bool => str_starts_with($relative, $prefix)) !== []) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (preg_match_all('/\buppercase\b|\btext-\[\d+px\]/', $contents, $matches) > 0) {
            $violations[] = $relative.': '.implode(', ', array_unique($matches[0]));
        }
    }

    expect($violations)->toBe([]);
});
