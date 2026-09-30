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

/** @return array<string, string> css variable name without the dashes => hex */
function designTokenStripStops(string $theme): array
{
    $source = (string) file_get_contents(repoPath('packages/shared/src/tokens.ts'));

    preg_match('/export const dayStrip'.$theme.': readonly DayStripStop\[\] = \[(.*?)\];/s', $source, $block);
    preg_match_all("/name: '(\w+)', minute: [^,]+, color: '(#[0-9A-Fa-f]{6})'/", $block[1] ?? '', $stops, PREG_SET_ORDER);

    $colors = [];

    foreach ($stops as [, $name, $hex]) {
        $colors['strip-'.$name] = strtoupper($hex);
    }

    return $colors;
}

/** @return array<string, array{size: int, maxSize: int|null, lineHeight: string}> css role => its row in typeScale */
function designTokenTypeScale(): array
{
    $source = (string) file_get_contents(repoPath('packages/shared/src/tokens.ts'));

    preg_match('/export const typeScale = \{(.*?)\n\} as const;/s', $source, $block);
    preg_match_all('/^\s*(\w+): \{ size: (\d+), (?:maxSize: (\d+), )?lineHeight: ([\d.]+),/m', $block[1] ?? '', $rows, PREG_SET_ORDER);

    $scale = [];

    foreach ($rows as [, $role, $size, $maxSize, $lineHeight]) {
        $scale[strtolower((string) preg_replace('/[A-Z]/', '-$0', $role))] = [
            'size' => (int) $size,
            'maxSize' => $maxSize === '' ? null : (int) $maxSize,
            'lineHeight' => $lineHeight,
        ];
    }

    return $scale;
}

function designTokenRem(int $pixels): string
{
    return rtrim(rtrim(number_format($pixels / 16, 4, '.', ''), '0'), '.').'rem';
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

it('declares every day-strip stop in the stylesheet with the same hex as the shared tokens', function (string $theme, string $selector): void {
    $stops = designTokenStripStops($theme);
    $css = designTokenCssColors($selector);

    expect($stops)->toHaveCount(6);

    foreach ($stops as $name => $hex) {
        expect($css[$name] ?? null)->toBe($hex, "{$selector} --{$name}");
    }
})->with([
    ['Light', ':root'],
    ['Dark', '.dark'],
]);

it('declares every role of the type scale in the stylesheet at the size the shared tokens give it', function (): void {
    $css = (string) file_get_contents(base_path('resources/css/app.css'));
    $scale = designTokenTypeScale();

    expect($scale)->toHaveCount(6);

    foreach ($scale as $role => ['size' => $size, 'maxSize' => $maxSize, 'lineHeight' => $lineHeight]) {
        preg_match('/--text-'.$role.':\s*([^;]+);/', $css, $value);
        preg_match('/--text-'.$role.'--line-height:\s*([^;]+);/', $css, $leading);

        $expected = $maxSize === null
            ? designTokenRem($size)
            : '/^clamp\('.preg_quote(designTokenRem($size), '/').',.*, '.preg_quote(designTokenRem($maxSize), '/').'\)$/';

        $maxSize === null
            ? expect($value[1] ?? null)->toBe($expected, "--text-{$role}")
            : expect($value[1] ?? '')->toMatch($expected, "--text-{$role}");
        expect($leading[1] ?? null)->toBe($lineHeight, "--text-{$role}--line-height");
    }
});

it('paints the page before the stylesheet loads with the paper of both themes, and nothing else', function (): void {
    $blade = (string) file_get_contents(resource_path('views/app.blade.php'));

    preg_match_all('/#[0-9A-Fa-f]{6}\b/', $blade, $hexes);

    expect(array_values(array_unique(array_map(strtoupper(...), $hexes[0]))))
        ->toEqualCanonicalizing([designTokenColors('light')['paper'], designTokenColors('dark')['paper']]);
});

it('declares every radius in the stylesheet at the size the shared tokens give it', function (): void {
    $source = (string) file_get_contents(repoPath('packages/shared/src/tokens.ts'));
    $css = (string) file_get_contents(base_path('resources/css/app.css'));

    preg_match('/export const radius = \{(.*?)\} as const;/', $source, $block);
    preg_match_all('/(\w+): (\d+)/', $block[1] ?? '', $radii, PREG_SET_ORDER);

    expect($radii)->toHaveCount(3);

    foreach ($radii as [, $name, $pixels]) {
        preg_match('/--radius-'.$name.':\s*([^;]+);/', $css, $value);

        expect($value[1] ?? null)->toBe(designTokenRem((int) $pixels), "--radius-{$name}");
    }
});

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
