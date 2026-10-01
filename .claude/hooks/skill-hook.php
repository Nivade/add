<?php

declare(strict_types=1);

// Claude Code hook logic for skill-hook.sh: names skills whose paths or keywords match, from a
// per-session cache of .ai/skills/*/SKILL.md frontmatter, already compiled to regex.

// stdout is a JSON-only contract; a vendor deprecation notice on autoload must not land there.
ini_set('display_errors', 'stderr');

// .ai/skills sits at the git root; vendor/ in the app, which a monorepo nests below it.
$root = getenv('CLAUDE_PROJECT_DIR') ?: dirname(__DIR__, 2);
$app = rtrim("{$root}/backend/", '/');
$autoload = "{$app}/vendor/autoload.php";

if (! is_file($autoload)) {
    exit(0);
}

require $autoload;

if (! class_exists(Symfony\Component\Yaml\Yaml::class)) {
    exit(0);
}

$input = json_decode((string) file_get_contents('php://stdin'), true);

if (! is_array($input)) {
    exit(0);
}

$event = $input['hook_event_name'] ?? null;
$sessionId = is_string($input['session_id'] ?? null) && $input['session_id'] !== '' ? $input['session_id'] : 'default';
$skills = skillHookSkills($root, $sessionId);
$matches = [];

if ($event === 'UserPromptSubmit') {
    $prompt = is_string($input['prompt'] ?? null) ? $input['prompt'] : '';

    foreach ($skills as $skill) {
        if ($skill['keywordRegex'] !== null && @preg_match($skill['keywordRegex'], $prompt) === 1) {
            $matches[] = $skill['name'];
        }
    }
} elseif ($event === 'PreToolUse' && in_array($input['tool_name'] ?? null, ['Edit', 'Write'], true)) {
    $path = $input['tool_input']['file_path'] ?? null;

    if (is_string($path) && $path !== '') {
        $relatives = skillHookRelativePaths($path, $root, $app);

        foreach ($skills as $skill) {
            if (skillHookPathsMatch($skill['paths'], $relatives)) {
                $matches[] = $skill['name'];
            }
        }
    }
}

$matches = array_values(array_unique($matches));

if ($matches !== []) {
    echo json_encode([
        'hookSpecificOutput' => [
            'hookEventName' => $event,
            'additionalContext' => 'Matching skills available: '.implode(', ', $matches).'. Invoke with the Skill tool if relevant.',
        ],
    ], JSON_THROW_ON_ERROR);
}

exit(0);

/**
 * @return list<string> from the git root and, inside a nested app, from the app too, since a skill's globs may assume either
 */
function skillHookRelativePaths(string $path, string $root, string $app): array
{
    $relatives = [];

    foreach (array_unique([$root, $app]) as $base) {
        if (str_starts_with($path, "{$base}/")) {
            $relatives[] = substr($path, strlen($base) + 1);
        }
    }

    return $relatives === [] ? [$path] : $relatives;
}

/**
 * @param  list<array{regex: string, basenameOnly: bool}>  $pathSpecs
 * @param  list<string>  $relatives
 */
function skillHookPathsMatch(array $pathSpecs, array $relatives): bool
{
    foreach ($pathSpecs as $pathSpec) {
        foreach ($relatives as $relative) {
            if (preg_match($pathSpec['regex'], $pathSpec['basenameOnly'] ? basename($relative) : $relative) === 1) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @return list<array{name: string, paths: list<array{regex: string, basenameOnly: bool}>, keywordRegex: string|null}>
 */
function skillHookSkills(string $root, string $sessionId): array
{
    $cache = sprintf(
        '%s/devtools-skill-hook-%s-%s.json',
        sys_get_temp_dir(),
        substr(hash('xxh3', $root), 0, 12),
        preg_replace('/[^A-Za-z0-9_-]/', '_', $sessionId),
    );

    if (is_file($cache)) {
        $cached = json_decode((string) file_get_contents($cache), true);

        if (is_array($cached)) {
            return $cached;
        }
    }

    $skills = [];

    foreach (glob("{$root}/.ai/skills/*/SKILL.md") ?: [] as $file) {
        $skill = skillHookParse((string) file_get_contents($file));

        if ($skill !== null) {
            $skills[] = $skill;
        }
    }

    file_put_contents($cache, json_encode($skills, JSON_THROW_ON_ERROR));

    return $skills;
}

/**
 * @return array{name: string, paths: list<array{regex: string, basenameOnly: bool}>, keywordRegex: string|null}|null
 */
function skillHookParse(string $markdown): ?array
{
    if (preg_match('/\A---\R(.*?)\R---\R/s', $markdown, $match) !== 1) {
        return null;
    }

    try {
        $data = Symfony\Component\Yaml\Yaml::parse($match[1]);
    } catch (Throwable) {
        return null;
    }

    if (! is_array($data) || ! is_string($data['name'] ?? null) || $data['name'] === '') {
        return null;
    }

    $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
    $keywords = array_values(array_filter(skillHookStrings($metadata['keywords'] ?? null), skillHookValidRegex(...)));

    return [
        'name' => $data['name'],
        'paths' => array_map(skillHookCompilePath(...), skillHookStrings($data['paths'] ?? null)),
        'keywordRegex' => $keywords === [] ? null : '/'.implode('|', array_map(static fn (string $keyword): string => '(?:'.$keyword.')', $keywords)).'/i',
    ];
}

// A keyword regex a skill author got wrong should drop that one keyword, not break the others.
function skillHookValidRegex(string $keyword): bool
{
    return @preg_match('/(?:'.$keyword.')/', '') !== false;
}

/**
 * @return list<string>
 */
function skillHookStrings(mixed $value): array
{
    $items = is_string($value) ? explode(',', $value) : (is_array($value) ? $value : []);

    return array_values(array_filter(array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $items), static fn (string $item): bool => $item !== ''));
}

/**
 * @return array{regex: string, basenameOnly: bool} basenameOnly for a slash-free glob, which names a file anywhere
 */
function skillHookCompilePath(string $glob): array
{
    return ['regex' => skillHookGlobToRegex($glob), 'basenameOnly' => ! str_contains($glob, '/')];
}

/**
 * `**\/` matches zero or more directories, a lone `**` matches anything, `*` stops at `/`.
 */
function skillHookGlobToRegex(string $glob): string
{
    $pattern = preg_replace_callback('#\*\*/|\*\*|\*|\?|[.+^$(){}|\[\]\\\\]#', static function (array $match): string {
        return match ($match[0]) {
            '**/' => '(?:.*/)?',
            '**' => '.*',
            '*' => '[^/]*',
            '?' => '[^/]',
            default => preg_quote($match[0], '#'),
        };
    }, str_replace('\\', '/', $glob));

    return '#^'.$pattern.'$#';
}
