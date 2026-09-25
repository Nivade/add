<?php

declare(strict_types=1);

use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Models\Concerns\StoresDatesInUtc;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

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

// A model without it writes a date on the person's clock as that wall clock, and every comparison drifts by their offset.
it('stores every model\'s dates as UTC instants', function () {
    foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
        $model = 'App\\Models\\'.basename($file, '.php');

        expect(class_uses_recursive($model))->toContain(StoresDatesInUtc::class);
    }
});

// .ai/rules/api-and-data.md: laravel-data replaces API Resources.
it('has no API resources', function () {
    expect(is_dir(app_path('Http/Resources')))->toBeFalse();
});

// .ai/rules/api-and-data.md: an array literal skips the constructor's type checks and meets the input mapper.
it('builds Data from literals with new, never from an array', function () {
    $files = phpSourceFiles();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('tests')));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    foreach ($files as $file) {
        expect(preg_match('/\b(?:\w+Data|self|static)::(?:from|optional)\(\s*\[/', (string) file_get_contents($file)))
            ->toBe(0, $file);
    }
});

// .ai/rules/domain-model.md: the spec's App/Domain tree is deliberately not built.
it('stays flat rather than growing a domain tree', function () {
    expect(is_dir(app_path('Domain')))->toBeFalse();
});

// .ai/rules/domain-model.md: a step is reached through the session that offered it.
it('keeps user_id off steps', function () {
    expect(Schema::hasColumn('steps', 'user_id'))->toBeFalse();
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

// A scheduled name that nothing answers fails every minute in silence, so the schedule is checked here.
it('schedules only commands that exist', function () {
    $registered = array_keys(Artisan::all());

    foreach (app(Schedule::class)->events() as $event) {
        preg_match('/artisan[\'"]? (\S+)/', (string) $event->command, $matches);

        expect($registered)->toContain($matches[1] ?? $event->command);
    }
});

// .ai/rules/support-and-concerns.md: Support holds adapters and calculation, never a caller of the layers above it.
arch('Support depends on nothing above it')
    ->expect('App\Support')
    ->not->toUse(['App\Actions', 'App\Http']);

// .ai/rules/support-and-concerns.md: a Support class with subclassable state is a seam nobody asked for.
arch('every Support class is final')
    ->expect('App\Support')
    ->classes()
    ->toBeFinal()
    ->ignoring(App\Support\NextAction\Rung::class);

// .ai/rules/support-and-concerns.md: every other trait moved to its layer; nothing new lands here.
it('keeps App\Concerns down to the Fortify validation traits', function () {
    $files = array_map(basename(...), glob(app_path('Concerns/*.php')) ?: []);

    expect($files)->toBe(['PasswordValidationRules.php', 'ProfileValidationRules.php']);
});
