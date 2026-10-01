<?php

declare(strict_types=1);

// Branch lifecycle hooks for Claude Code: `tier <from> [<to>]` sizes a diff for review, `gate` guards MR/PR merges, `merged` spots a merged branch, `plans` refreshes the generated .ai/plans view.

ini_set('display_errors', 'stderr');

$root = getenv('CLAUDE_PROJECT_DIR') ?: dirname(__DIR__, 2);
$app = rtrim("{$root}/backend/", '/');

/**
 * @return array<array-key, mixed>
 */
function lcStdin(): array
{
    $input = json_decode((string) file_get_contents('php://stdin'), true);

    return is_array($input) ? $input : [];
}

function lcPrintTier(string $root, string $app, ?string $from, string $to): void
{
    $default = lcDefaultBranch($root);
    $from ??= $default === null ? null : lcForkPoint($root, $default, $to);

    echo json_encode($from === null ? lcTierResult('full', false, 'unknown', 0, 0) : lcTier($root, $app, $from, $to), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
}

/**
 * @param  array<array-key, mixed>  $input
 */
function lcPrintGate(string $root, string $app, array $input): void
{
    $command = is_string($input['tool_input']['command'] ?? null) ? $input['tool_input']['command'] : '';
    $reason = ($input['tool_name'] ?? null) === 'Bash' && str_contains($command, 'merge') ? lcGate($root, $app, $command) : null;

    if ($reason !== null) {
        echo json_encode(['hookSpecificOutput' => ['hookEventName' => 'PreToolUse', 'permissionDecision' => 'deny', 'permissionDecisionReason' => $reason]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
    }
}

function lcPrintMerged(string $root): void
{
    $notice = lcMerged($root);

    if ($notice !== null) {
        echo json_encode(['hookSpecificOutput' => ['hookEventName' => 'UserPromptSubmit', 'additionalContext' => $notice]], JSON_THROW_ON_ERROR), "\n";
    }
}

function lcPrintPlans(string $app): void
{
    $runner = lcRunner($app);

    if ($runner !== null) {
        lcExec($app, [PHP_BINARY, $runner, 'devtools:plans', '--quiet', '--no-interaction'], 50);
    }
}

// Review tiers.

const LC_LOCKFILES = ['composer.lock', 'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'CHANGELOG.md'];

const LC_MANIFESTS = ['composer.json', 'package.json'];

const LC_DEPENDENCY_KEYS = [
    'require', 'require-dev', 'conflict', 'replace', 'provide',
    'dependencies', 'devDependencies', 'peerDependencies', 'optionalDependencies', 'overrides',
];

const LC_NO_REVIEW = ['#(^|/)\.(ai|claude)/plans/#', '#(^|/)\.ai/(findings|RESUME)\.md$#'];

const LC_DOCS = ['#\.md$#', '#(^|/)\.ai/#', '#(^|/)\.claude/(skills|rules)/#'];

const LC_SECURITY = [
    '#(^|/)routes/#', '#(^|/)Http/Middleware/#', '#(^|/)Policies/#', '#(^|/)Auth/#',
    '#(^|/)config/(auth|sanctum|cors)\.php$#', '#(^|/)\.env\.example$#',
    '#^\.gitlab-ci\.yml$#', '#^\.gitlab/#', '#^\.github/workflows/#', '#^\.claude/settings\.json$#', '#^\.claude/hooks/#',
];

/**
 * @param  list<string>|null  $within  only count these paths, when given
 * @return array{tier: string, security: bool, reason: string, files: int, lines: int}
 */
function lcTier(string $root, string $app, string $from, string $to, ?array $within = null): array
{
    $changes = lcNumstat($root, $from, $to);

    if ($changes === null) {
        return lcTierResult('full', false, 'unknown', 0, 0);
    }

    // devtools:sync --managed reads the working tree, which is only what is judged when that is HEAD.
    $managed = $to === 'HEAD' ? null : [];
    $files = 0;
    $lines = 0;
    $docsOnly = true;
    $security = false;

    foreach ($changes as [$added, $deleted, $path]) {
        if (($within !== null && ! in_array($path, $within, true)) || lcNeedsNoReview($root, $from, $to, $path) || isset(($managed ??= lcManaged($app))[$path])) {
            continue;
        }

        $files++;
        $lines += $added + $deleted;
        $docsOnly = $docsOnly && lcMatchesAny(LC_DOCS, $path);
        $security = $security || lcMatchesAny(LC_SECURITY, $path);
    }

    [$fullLines, $fullFiles] = lcThresholds($app);

    $tier = match (true) {
        $files === 0 => 'none',
        $docsOnly => 'docs',
        $lines > $fullLines || $files > $fullFiles => 'full',
        default => 'light',
    };

    return lcTierResult($tier, $security, $files === 0 ? 'only plans, lockfiles, dependency bumps and unchanged devtools files' : "{$files} files, {$lines} lines", $files, $lines);
}

/**
 * @return list<array{int, int, string}>|null added, deleted, path from the git root; null when git fails
 */
function lcNumstat(string $root, string $from, string $to): ?array
{
    $raw = lcGit($root, ['diff', '--numstat', '--no-renames', '-z', $from, $to]);

    if ($raw === null) {
        return null;
    }

    $changes = [];

    foreach (array_filter(explode("\0", $raw), static fn (string $record): bool => $record !== '') as $record) {
        [$added, $deleted, $path] = array_pad(explode("\t", ltrim($record, "\n"), 3), 3, '');
        $changes[] = [(int) $added, (int) $deleted, $path];
    }

    return $changes;
}

/**
 * @return array{tier: string, security: bool, reason: string, files: int, lines: int}
 */
function lcTierResult(string $tier, bool $security, string $reason, int $files, int $lines): array
{
    return ['tier' => $tier, 'security' => $security, 'reason' => $reason, 'files' => $files, 'lines' => $lines];
}

function lcNeedsNoReview(string $root, string $from, string $to, string $path): bool
{
    $name = basename($path);

    return match (true) {
        in_array($name, LC_LOCKFILES, true), lcMatchesAny(LC_NO_REVIEW, $path) => true,
        in_array($name, LC_MANIFESTS, true) => lcManifestUnchanged($root, "{$from}:{$path}", "{$to}:{$path}"),
        default => false,
    };
}

function lcManifestUnchanged(string $root, string $before, string $after): bool
{
    $old = lcManifestWithoutDependencies($root, $before);
    $new = lcManifestWithoutDependencies($root, $after);

    return $old !== null && $new !== null && $old === $new;
}

/**
 * @return array<array-key, mixed>|null
 */
function lcManifestWithoutDependencies(string $root, string $object): ?array
{
    $decoded = lcJson($root, ['git', 'show', $object]);

    if ($decoded === null) {
        return null;
    }

    // devtools records the agent context it wrote here, so a sync rewrites it.
    if (is_array($decoded['extra']['devtools'] ?? null)) {
        unset($decoded['extra']['devtools']['agent-context']);
    }

    return array_diff_key($decoded, array_flip(LC_DEPENDENCY_KEYS));
}

function lcRunner(string $app): ?string
{
    return match (true) {
        is_file("{$app}/artisan") => "{$app}/artisan",
        is_file("{$app}/vendor/bin/testbench") => "{$app}/vendor/bin/testbench",
        default => null,
    };
}

/**
 * @return array<string, int> paths from the git root that still match what devtools ships
 */
function lcManaged(string $app): array
{
    $runner = lcRunner($app);
    $paths = $runner === null ? null : lcJson($app, [PHP_BINARY, $runner, 'devtools:sync', '--managed', '--no-interaction'], 20);

    return is_array($paths) ? array_flip(array_values(array_filter($paths, 'is_string'))) : [];
}

/**
 * @return array{int, int}
 */
function lcThresholds(string $app): array
{
    $composer = json_decode((string) @file_get_contents("{$app}/composer.json"), true);
    $review = is_array($composer) ? ($composer['extra']['devtools']['review'] ?? []) : [];

    return [
        is_int($review['full-lines'] ?? null) ? $review['full-lines'] : 400,
        is_int($review['full-files'] ?? null) ? $review['full-files'] : 15,
    ];
}

/**
 * @param  list<string>  $patterns
 */
function lcMatchesAny(array $patterns, string $path): bool
{
    return array_any($patterns, static fn (string $pattern): bool => preg_match($pattern, $path) === 1);
}

// Merge gate.

const LC_REVIEW_LINE = '/^Review: (none|docs|light|full) @ ([0-9a-f]{7,40})\b/m';

function lcGate(string $root, string $app, string $command): ?string
{
    $mr = lcMergeTarget($root, $command);

    if ($mr === null) {
        return null;
    }

    ['branch' => $branch, 'description' => $description, 'head' => $head] = $mr;
    $reviewed = preg_match(LC_REVIEW_LINE, $description, $review) === 1 ? $review[2] : null;

    if ($head === null) {
        return $reviewed === null ? "finish-branch: {$branch} has no MR/PR with a `Review:` line. Run finish-branch on it before merging." : null;
    }

    if ($reviewed !== null && str_starts_with($head, $reviewed)) {
        return null;
    }

    $needed = $reviewed === null ? [$head] : [$reviewed, $head];

    if (! array_all($needed, static fn (string $commit): bool => lcHasCommit($root, $commit))) {
        lcGit($root, ['fetch', '-q', 'origin', $branch]);
    }

    $default = lcDefaultBranch($root);
    $fork = $default === null ? null : lcForkPoint($root, $default, $head);

    if ($fork === null || ! array_all($needed, static fn (string $commit): bool => lcHasCommit($root, $commit))) {
        return "finish-branch: can't read {$branch}'s commits to check its review. Fetch the branch, or run finish-branch again.";
    }

    if ($reviewed === null) {
        $tier = lcTier($root, $app, $fork, $head);

        return $tier['tier'] === 'none' ? null : "finish-branch: {$branch} has no MR/PR with a `Review:` line and its diff needs review ({$tier['reason']}). Run finish-branch on it before merging.";
    }

    // Only the branch's own diff counts, so merging the default branch in after review asks for nothing.
    $own = array_column(lcNumstat($root, $fork, $head) ?? [], 2);
    $tier = lcTier($root, $app, $reviewed, $head, $own);

    return $tier['tier'] === 'none' ? null : "finish-branch: {$branch} gained {$tier['tier']} changes after the review at {$reviewed} ({$tier['reason']}). Run finish-branch again before merging.";
}

function lcHasCommit(string $root, string $revision): bool
{
    return lcGit($root, ['cat-file', '-e', "{$revision}^{commit}"]) !== null;
}

/**
 * @return array{branch: string, description: string, head: ?string}|null
 */
function lcMergeTarget(string $root, string $command): ?array
{
    foreach (lcSegments($command) as $segment) {
        $tokens = lcTokens($segment);

        $target = match (true) {
            $tokens === [] => null,
            lcCommandIs($tokens, ['gh', 'pr', 'merge']) => lcGhView($root, lcArgsAfter($tokens, 'merge', ['-t', '--subject', '-b', '--body', '-F', '--body-file', '-A', '--author-email', '--match-head-commit'])),
            lcCommandIs($tokens, ['glab', 'mr', 'merge']) => lcGlabView($root, lcArgsAfter($tokens, 'merge', ['-m', '--message', '--squash-message', '--sha'])),
            lcCommandIs($tokens, ['git', 'merge']) => lcLocalMergeTarget($root, $tokens),
            default => null,
        };

        if ($target !== null) {
            return $target;
        }
    }

    return null;
}

/**
 * @param  array{arg: ?string, repo: list<string>}  $args
 * @return array{branch: string, description: string, head: ?string}|null
 */
function lcGhView(string $root, array $args): ?array
{
    $view = lcBinaryExists('gh') ? lcJson($root, ['gh', 'pr', 'view', ...($args['arg'] === null ? [] : [$args['arg']]), ...$args['repo'], '--json', 'headRefName,headRefOid,body']) : null;

    return lcView($view, 'headRefName', 'body', 'headRefOid');
}

/**
 * @param  array{arg: ?string, repo: list<string>}  $args
 * @return array{branch: string, description: string, head: ?string}|null
 */
function lcGlabView(string $root, array $args): ?array
{
    $view = lcBinaryExists('glab') ? lcJson($root, ['glab', 'mr', 'view', ...($args['arg'] === null ? [] : [ltrim($args['arg'], '!')]), ...$args['repo'], '-F', 'json']) : null;

    return lcView($view, 'source_branch', 'description', 'sha');
}

/**
 * @param  array<array-key, mixed>|null  $view
 * @return array{branch: string, description: string, head: ?string}|null
 */
function lcView(?array $view, string $branchKey, string $descriptionKey, string $headKey): ?array
{
    if (! is_string($view[$branchKey] ?? null)) {
        return null;
    }

    return [
        'branch' => $view[$branchKey],
        'description' => is_string($view[$descriptionKey] ?? null) ? $view[$descriptionKey] : '',
        'head' => is_string($view[$headKey] ?? null) ? $view[$headKey] : null,
    ];
}

/**
 * @param  list<string>  $tokens
 * @return array{branch: string, description: string, head: ?string}|null
 */
function lcLocalMergeTarget(string $root, array $tokens): ?array
{
    $current = lcCurrentBranch($root);
    $target = lcArgsAfter($tokens, 'merge', ['-m', '-F', '--file', '-s', '--strategy', '-X', '--strategy-option', '--into-name'])['arg'];

    if ($target === null || $current === null || $current !== lcDefaultBranch($root)) {
        return null;
    }

    $branch = (string) preg_replace('#^origin/#', '', $target);
    $github = lcIsGithub($root);
    $request = lcRequest($root, $github, $branch, merged: false);

    if ($request !== null) {
        $args = ['arg' => (string) $request['number'], 'repo' => []];
        $view = $github ? lcGhView($root, $args) : lcGlabView($root, $args);

        if ($view !== null) {
            return $view;
        }
    }

    // No MR/PR to read: judge the branch itself, which passes only when its diff needs no review.
    return ['branch' => $branch, 'description' => '', 'head' => lcGit($root, ['rev-parse', '--verify', '--quiet', "{$target}^{commit}"])];
}

/**
 * @return list<string>
 */
function lcSegments(string $command): array
{
    return array_values(array_filter(array_map(trim(...), preg_split('/&&|\|\||;/', $command) ?: [])));
}

/**
 * @return list<string>
 */
function lcTokens(string $segment): array
{
    preg_match_all('/"[^"]*"|\'[^\']*\'|\S+/', $segment, $matches);

    return array_map(static fn (string $token): string => trim($token, '\'"'), $matches[0]);
}

/**
 * @param  list<string>  $tokens
 * @param  list<string>  $subcommand
 */
function lcCommandIs(array $tokens, array $subcommand): bool
{
    if (count($tokens) < count($subcommand) || basename($tokens[0]) !== $subcommand[0]) {
        return false;
    }

    foreach (array_slice($subcommand, 1) as $index => $word) {
        if (($tokens[$index + 1] ?? null) !== $word) {
            return false;
        }
    }

    return true;
}

/**
 * @param  list<string>  $tokens
 * @param  list<string>  $valueFlags
 * @return array{arg: ?string, repo: list<string>}
 */
function lcArgsAfter(array $tokens, string $subcommand, array $valueFlags): array
{
    $index = array_search($subcommand, $tokens, true);
    $rest = $index === false ? [] : array_slice($tokens, $index + 1);
    $found = ['arg' => null, 'repo' => []];

    for ($i = 0; $i < count($rest); $i++) {
        $token = $rest[$i];

        match (true) {
            in_array($token, ['-R', '--repo'], true) => $found['repo'] = ['--repo', $rest[++$i] ?? ''],
            str_starts_with($token, '--repo=') => $found['repo'] = ['--repo', substr($token, 7)],
            in_array($token, $valueFlags, true) => $i++,
            str_starts_with($token, '-') => null,
            default => $found['arg'] ??= $token,
        };
    }

    return $found;
}

// Merge detection.

const LC_MERGED_CACHE_SECONDS = 600;

const LC_UNMERGED_CACHE_SECONDS = 120;

function lcMerged(string $root): ?string
{
    $branch = lcCurrentBranch($root);

    if ($branch === null || $branch === lcDefaultBranch($root) || lcGit($root, ['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{u}']) === null) {
        return null;
    }

    $github = lcIsGithub($root);
    $cache = sys_get_temp_dir().'/devtools-merged-'.substr(hash('xxh3', "{$root}\0{$branch}"), 0, 16).'.json';
    $cached = json_decode((string) @file_get_contents($cache), true);

    $fresh = is_array($cached) && is_int($cached['checked'] ?? null)
        && time() - $cached['checked'] < (is_int($cached['number'] ?? null) ? LC_MERGED_CACHE_SECONDS : LC_UNMERGED_CACHE_SECONDS);

    if ($fresh) {
        $number = $cached['number'] ?? null;
    } else {
        $request = lcRequest($root, $github, $branch, merged: true);
        // A reused branch name: an old merged MR whose head does not contain this work is not this branch's merge.
        $ours = $request !== null && $request['head'] !== null && lcGit($root, ['merge-base', '--is-ancestor', 'HEAD', $request['head']]) !== null;
        $number = $ours ? $request['number'] : null;
        @file_put_contents($cache, json_encode(['checked' => time(), 'number' => $number], JSON_THROW_ON_ERROR), LOCK_EX);
    }

    if (! is_int($number)) {
        return null;
    }

    $label = $github ? "PR #{$number}" : "MR !{$number}";

    return "{$label} from {$branch} is merged: run the after-merge skill before anything else.";
}

/**
 * The branch's open MR/PR, or its merged one; null without one or without gh/glab.
 *
 * @return array{number: int, head: ?string}|null
 */
function lcRequest(string $root, bool $github, string $branch, bool $merged): ?array
{
    $list = match (true) {
        $github && lcBinaryExists('gh') => lcJson($root, ['gh', 'pr', 'list', '--head', $branch, '--state', $merged ? 'merged' : 'open', '--json', 'number,headRefOid'], 3),
        ! $github && lcBinaryExists('glab') => lcJson($root, ['glab', 'mr', 'list', '--source-branch', $branch, ...($merged ? ['--merged'] : []), '-F', 'json'], 3),
        default => null,
    };
    $number = $list[0][$github ? 'number' : 'iid'] ?? null;
    $head = $list[0][$github ? 'headRefOid' : 'sha'] ?? null;

    return is_int($number) ? ['number' => $number, 'head' => is_string($head) ? $head : null] : null;
}

// Git and process helpers.

/**
 * @param  list<string>  $command
 */
function lcExec(string $cwd, array $command, int $timeoutSeconds = 5): ?string
{
    $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);

    if (! is_resource($process)) {
        return null;
    }

    stream_set_blocking($pipes[1], false);
    $deadline = microtime(true) + $timeoutSeconds;
    $output = '';
    $status = proc_get_status($process);

    while ($status['running'] && microtime(true) < $deadline) {
        $output .= stream_get_contents($pipes[1]);
        usleep(20_000);
        $status = proc_get_status($process);
    }

    if ($status['running']) {
        proc_terminate($process);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return null;
    }

    $output .= stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process) === 0 ? rtrim($output, "\n") : null;
}

/**
 * @param  list<string>  $args
 */
function lcGit(string $root, array $args): ?string
{
    return lcExec($root, ['git', ...$args]);
}

/**
 * @param  list<string>  $command
 * @return array<array-key, mixed>|null
 */
function lcJson(string $cwd, array $command, int $timeoutSeconds = 5): ?array
{
    $json = lcExec($cwd, $command, $timeoutSeconds);
    $decoded = $json === null ? null : json_decode($json, true);

    return is_array($decoded) ? $decoded : null;
}

function lcBinaryExists(string $binary): bool
{
    return array_any(explode(PATH_SEPARATOR, (string) getenv('PATH')), static fn (string $dir): bool => $dir !== '' && is_executable("{$dir}/{$binary}"));
}

function lcIsGithub(string $root): bool
{
    return str_contains(strtolower(lcGit($root, ['remote', 'get-url', 'origin']) ?? ''), 'github');
}

function lcCurrentBranch(string $root): ?string
{
    $branch = lcGit($root, ['symbolic-ref', '--short', '-q', 'HEAD']);

    return $branch === null || $branch === '' ? null : $branch;
}

function lcDefaultBranch(string $root): ?string
{
    $ref = lcGit($root, ['symbolic-ref', '--quiet', 'refs/remotes/origin/HEAD']);

    if ($ref !== null && preg_match('#^refs/remotes/origin/(.+)$#', $ref, $match) === 1) {
        return $match[1];
    }

    foreach (['main', 'master'] as $name) {
        if (lcGit($root, ['show-ref', '--verify', '--quiet', "refs/heads/{$name}"]) !== null) {
            return $name;
        }
    }

    $composer = json_decode((string) @file_get_contents("{$root}/composer.json"), true);
    $configured = is_array($composer) ? ($composer['extra']['devtools']['default-branch'] ?? null) : null;

    return is_string($configured) && $configured !== '' ? $configured : null;
}

function lcForkPoint(string $root, string $default, string $revision): ?string
{
    $base = lcGit($root, ['rev-parse', '--verify', '--quiet', "origin/{$default}"]) !== null ? "origin/{$default}" : $default;

    return lcGit($root, ['merge-base', $base, $revision]);
}

// Dispatch last: the LC_* constants above are defined at runtime, in file order.
match ($argv[1] ?? null) {
    'tier' => lcPrintTier($root, $app, $argv[2] ?? null, $argv[3] ?? 'HEAD'),
    'gate' => lcPrintGate($root, $app, lcStdin()),
    'merged' => lcPrintMerged($root),
    'plans' => lcPrintPlans($app),
    default => null,
};
