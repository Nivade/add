<?php

declare(strict_types=1);

/** @return list<string> */
function documentFiles(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(repoPath('.ai')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'md') {
            continue;
        }

        // guidelines.md is Boost's output; product-spec.md is the spec, verbatim.
        if (in_array($file->getFilename(), ['guidelines.md', 'product-spec.md'], true)) {
            continue;
        }

        // Skills come from the sibling repos and teach with example paths of their own.
        if (str_contains($file->getPathname(), '/.ai/skills/')) {
            continue;
        }

        $files[] = $file->getPathname();
    }

    $files[] = repoPath('CLAUDE.md');

    return $files;
}

function documentName(string $file): string
{
    $root = (string) realpath(repoPath(''));

    return ltrim(str_replace($root, '', (string) realpath($file)), '/');
}

/** Documents write paths from the repo root or from `backend/`, and both are legitimate. */
function documentedPathExists(string $path): bool
{
    return file_exists(repoPath($path)) || file_exists(base_path($path));
}

/**
 * @param  callable(string, string): list<string>  $violationsIn
 * @return list<string>
 */
function documentViolations(callable $violationsIn): array
{
    $violations = [];

    foreach (documentFiles() as $file) {
        foreach ($violationsIn((string) file_get_contents($file), $file) as $violation) {
            $violations[] = documentName($file).': '.$violation;
        }
    }

    return $violations;
}

// .ai/rules/general.md: a document naming a file the build renamed is worse than no document.
it('resolves every relative link in an agent document', function () {
    $violations = documentViolations(function (string $contents, string $file): array {
        preg_match_all('/\]\(([^)]+)\)/', $contents, $matches);

        $broken = [];

        foreach ($matches[1] as $target) {
            if (preg_match('/^(https?:|mailto:|\#)/', $target) === 1) {
                continue;
            }

            if (! file_exists(dirname($file).'/'.strtok($target, '#'))) {
                $broken[] = 'links to '.$target;
            }
        }

        return $broken;
    });

    expect($violations)->toBe([]);
});

// .ai/rules/general.md: state the decision rather than the shape, and when the shape is named, name the real one.
it('names only paths that exist', function () {
    // Designed but unbuilt. Delete an entry in the change that builds it.
    $planned = [];

    foreach ($planned as $path) {
        expect(documentedPathExists($path))->toBeFalse($path.' exists — drop it from the planned list');
    }

    $violations = documentViolations(function (string $contents) use ($planned): array {
        preg_match_all('/`([^`\s]+\.(?:php|ts|tsx|js|json|yaml|yml|xml|lock|example))`/', $contents, $matches);

        $missing = [];

        foreach ($matches[1] as $path) {
            // A bare filename names no location; `OutputCleaner.php` is a vendor symptom, not a repo path.
            if (! str_contains($path, '/') || str_contains($path, '*') || in_array($path, $planned, true)) {
                continue;
            }

            if (! documentedPathExists($path)) {
                $missing[] = 'names '.$path;
            }
        }

        return $missing;
    });

    expect($violations)->toBe([]);
});

// .ai/rules/toolchain.md: root scripts are the documented way in, so a rename has to reach the documents.
it('names only root scripts that exist', function () {
    $scripts = array_keys(json_decode((string) file_get_contents(repoPath('package.json')), true)['scripts']);

    $violations = documentViolations(function (string $contents) use ($scripts): array {
        preg_match_all('/npm run ([a-z][a-z0-9:_-]*)/', $contents, $matches);

        return array_values(array_map(
            fn (string $script): string => 'names npm run '.$script,
            array_filter($matches[1], fn (string $script): bool => ! in_array($script, $scripts, true))
        ));
    });

    expect($violations)->toBe([]);
});

it('names only composer scripts that exist', function () {
    $builtIn = ['install', 'update', 'require', 'remove', 'show', 'run', 'outdated', 'audit', 'dump-autoload', 'create-project'];
    $scripts = array_keys(json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']);

    $violations = documentViolations(function (string $contents) use ($builtIn, $scripts): array {
        preg_match_all('/composer ([a-z][a-z0-9:_-]*)/', $contents, $matches);

        return array_values(array_map(
            fn (string $script): string => 'names composer '.$script,
            array_filter(
                $matches[1],
                fn (string $script): bool => ! in_array($script, $builtIn, true) && ! in_array($script, $scripts, true)
            )
        ));
    });

    expect($violations)->toBe([]);
});

// .ai/rules/overview.md: index.md is hand-maintained, so nothing else notices a rule file it forgot.
it('indexes every rule file', function () {
    $index = (string) file_get_contents(repoPath('.ai/rules/index.md'));

    foreach (glob(repoPath('.ai/rules/*.md')) ?: [] as $file) {
        if (in_array(basename($file), ['overview.md', 'index.md'], true)) {
            continue;
        }

        expect($index)->toContain(basename($file));
    }
});
