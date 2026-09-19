<?php

declare(strict_types=1);

use App\Enums\SessionOutcome;
use App\Enums\StepStatus;

/** @return list<string> */
function phpSourceFiles(): array
{
    $files = [];

    foreach (['app', 'config', 'routes', 'database'] as $directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path($directory))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

// .ai/rules/product-invariants.md: no failure words in the domain vocabulary.
it('keeps failure words out of the enums', function () {
    $words = ['failed', 'abandoned', 'overdue', 'missed'];

    foreach (glob(app_path('Enums/*.php')) ?: [] as $file) {
        $contents = strtolower((string) file_get_contents($file));

        foreach ($words as $word) {
            expect($contents)->not->toContain("'{$word}'");
        }
    }

    expect(array_column(SessionOutcome::cases(), 'value'))
        ->toBe(['continued', 'completed', 'stopped']);

    expect(array_column(StepStatus::cases(), 'value'))
        ->toBe(['pending', 'done', 'skipped']);
});

// .ai/rules/product-invariants.md: an invented deadline is not modelled at all.
it('never adds a due_at or overdue column', function () {
    foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
        $contents = (string) file_get_contents($file);

        expect($contents)->not->toContain("'due_at'")
            ->and($contents)->not->toContain("'overdue'");
    }
});

// .ai/rules/api-and-data.md: laravel-data replaces API Resources.
it('has no API resources', function () {
    expect(is_dir(app_path('Http/Resources')))->toBeFalse();
});

// .ai/rules/domain-model.md: the spec's App/Domain tree is deliberately not built.
it('stays flat rather than growing a domain tree', function () {
    expect(is_dir(app_path('Domain')))->toBeFalse();
});

// .ai/rules/ai-layer.md: one provider seam, not the spec's five named interfaces.
it('keeps the AI layer behind one contract', function () {
    foreach (['IntentParser', 'TaskDecomposer', 'CommitmentDetector', 'DocumentInterpreter', 'EmailInterpreter'] as $interface) {
        expect(file_exists(app_path("Contracts/{$interface}.php")))->toBeFalse($interface);
    }
});

it('gives every rule file paths frontmatter so the index can be regenerated', function () {
    foreach (glob(repoPath('.ai/rules/*.md')) ?: [] as $file) {
        if (in_array(basename($file), ['overview.md', 'index.md'], true)) {
            continue;
        }

        expect(file_get_contents($file))->toStartWith("---\npaths:");
    }
});

// .ai/rules/general.md: a source comment must not cite a document the reader cannot see.
it('keeps rule-file citations out of source comments', function () {
    foreach (phpSourceFiles() as $file) {
        $contents = (string) file_get_contents($file);

        expect(preg_match('#(\*|//).*(\.ai/rules|\.ai/plans|\.claude)#', $contents))->toBe(0, $file);
    }
});

// .ai/rules/testing.md: a non-compound use statement crashes a paratest worker.
it('never imports a global class in a Pest file', function () {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('tests')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        expect(preg_match('/^use [A-Za-z_][A-Za-z0-9_]*;$/m', (string) file_get_contents($file->getPathname())))
            ->toBe(0, $file->getPathname());
    }
});
