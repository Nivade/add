<?php

declare(strict_types=1);

use App\Attributes\OneThing;
use App\Attributes\PerUserCommandReader;
use App\Enums\SessionOutcome;
use App\Enums\StepStatus;
use App\Models\Concerns\StoresDatesInUtc;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Lody\Lody;
use Spatie\LaravelData\Support\DataConfig;

/** @param  class-string  $class */
function violatesOneThing(string $class, DataConfig $config, array &$seen = []): bool
{
    if (in_array($class, $seen, true)) {
        return false;
    }

    $seen[] = $class;

    $dataClass = $config->getDataClass($class);

    foreach ($dataClass->properties as $property) {
        if ($property->type->kind->isDataCollectable() && $property->type->dataCollectableClass !== null) {
            return true;
        }

        if ($property->type->kind->isDataObject() && $property->type->dataClass !== null
            && violatesOneThing($property->type->dataClass, $config, $seen)) {
            return true;
        }
    }

    return false;
}

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
it('keeps failure words out of the enums', function (): void {
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
it('never adds a due_at or overdue column', function (): void {
    foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
        $contents = (string) file_get_contents($file);

        expect($contents)->not->toContain("'due_at'")
            ->and($contents)->not->toContain("'overdue'");
    }
});

// A model without it writes a date on the person's clock as that wall clock, and every comparison drifts by their offset.
it("stores every model's dates as UTC instants", function (): void {
    foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
        $model = 'App\\Models\\'.basename($file, '.php');

        expect(class_uses_recursive($model))->toContain(StoresDatesInUtc::class);
    }
});

// .ai/rules/api-and-data.md: laravel-data replaces API Resources.
it('has no API resources', function (): void {
    expect(is_dir(app_path('Http/Resources')))->toBeFalse();
});

// .ai/rules/api-and-data.md: an array literal skips the constructor's type checks and meets the input mapper.
it('builds Data from literals with new, never from an array', function (): void {
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
it('stays flat rather than growing a domain tree', function (): void {
    expect(is_dir(app_path('Domain')))->toBeFalse();
});

// .ai/rules/domain-model.md: a step is reached through the session that offered it.
it('keeps user_id off steps', function (): void {
    expect(Schema::hasColumn('steps', 'user_id'))->toBeFalse();
});

// .ai/rules/ai-layer.md: one provider seam, not the spec's five named interfaces.
it('keeps the AI layer behind one contract', function (): void {
    foreach (['IntentParser', 'TaskDecomposer', 'CommitmentDetector', 'DocumentInterpreter', 'EmailInterpreter'] as $interface) {
        expect(file_exists(app_path("Contracts/{$interface}.php")))->toBeFalse($interface);
    }
});

it('gives every rule file paths frontmatter so the index can be regenerated', function (): void {
    foreach (glob(repoPath('.ai/rules/*.md')) ?: [] as $file) {
        if (in_array(basename($file), ['overview.md', 'index.md'], true)) {
            continue;
        }

        expect(file_get_contents($file))->toStartWith("---\npaths:");
    }
});

// .ai/rules/general.md: a source comment must not cite a document the reader cannot see.
it('keeps rule-file citations out of source comments', function (): void {
    foreach (phpSourceFiles() as $file) {
        $contents = (string) file_get_contents($file);

        expect(preg_match('#(\*|//).*(\.ai/rules|\.ai/plans|\.claude)#', $contents))->toBe(0, $file);
    }
});

// .ai/rules/testing.md: a non-compound use statement crashes a paratest worker.
it('never imports a global class in a Pest file', function (): void {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('tests')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        expect(preg_match('/^use [A-Za-z_][A-Za-z0-9_]*;$/m', (string) file_get_contents($file->getPathname())))
            ->toBe(0, $file->getPathname());
    }
});

// Two taps can both pass an unlocked open check, so every transition re-reads its session under the row lock.
it('locks the session before any transition reads it', function (): void {
    $notTransitions = ['BuildExecutionState', 'RecordExecutionEvent', 'StopSession'];

    foreach (glob(app_path('Actions/Sessions/*.php')) ?: [] as $file) {
        if (in_array(basename($file, '.php'), $notTransitions, true)) {
            continue;
        }

        $source = (string) file_get_contents($file);
        $transition = strpos($source, '->transition(function');

        expect($transition)->not->toBeFalse($file)
            ->and($source)->not->toContain('DB::transaction')
            ->and(preg_match('/->(paused_at|current_step_id|ended_at|intention_id)\b|currentStep(OrFail)?\(/', substr($source, 0, (int) $transition)))->toBe(0, $file);
    }
});

// routes/console.php builds the schedule from #[PerUserCommand] rather than a hand-written line per command.
it('schedules every per-user command', function (): void {
    $scheduled = [];

    foreach (app(Schedule::class)->events() as $event) {
        preg_match('/artisan[\'"]? (\S+)/', (string) $event->command, $matches);

        $scheduled[] = $matches[1] ?? $event->command;
    }

    foreach (Lody::classes(app_path('Actions')) as $class) {
        $perUserCommand = PerUserCommandReader::tryFor($class);

        if (! $perUserCommand instanceof App\Attributes\PerUserCommand) {
            continue;
        }

        expect($scheduled)->toContain($perUserCommand->name);
    }
});

// One renderer for domain exceptions, read from #[RespondsWith] in bootstrap/app.php.
it('renders no domain exception with its own render()', function (): void {
    $directories = array_merge(
        glob(app_path('Exceptions'), GLOB_ONLYDIR) ?: [],
        glob(app_path('Support/*/Exceptions'), GLOB_ONLYDIR) ?: [],
    );

    foreach ($directories as $directory) {
        foreach (glob("{$directory}/*.php") ?: [] as $file) {
            expect((string) file_get_contents($file))->not->toContain('function render(');
        }
    }
});

// One name per adapter, read from #[Driver] rather than repeated in a service provider's match.
it('gives every AI provider and calendar source one #[Driver] name, and no two share one', function (): void {
    $exempt = [
        App\Support\Ai\Providers\LoggingAiProvider::class,
        App\Support\Ai\Providers\ConsentGatedAiProvider::class,
        App\Support\Ai\Providers\NullAiProvider::class,
        App\Support\Calendar\Sources\NullCalendarSource::class,
    ];

    foreach ([
        'App\Support\Ai\Providers' => glob(app_path('Support/Ai/Providers/*.php')) ?: [],
        'App\Support\Calendar\Sources' => glob(app_path('Support/Calendar/Sources/*.php')) ?: [],
    ] as $namespace => $files) {
        $names = [];

        foreach ($files as $file) {
            $class = $namespace.'\\'.basename($file, '.php');

            if (in_array($class, $exempt, true)) {
                continue;
            }

            $attribute = new ReflectionClass($class)->getAttributes(App\Attributes\Driver::class)[0] ?? null;

            expect($attribute)->not->toBeNull($class);

            $name = $attribute->newInstance()->name;

            expect(in_array($name, $names, true))->toBeFalse("{$class} shares the driver name \"{$name}\"");

            $names[] = $name;
        }
    }
});

// The deep-link key, once: either a fixed #[NotificationKind] or a kind() the class answers itself.
it('gives every push notification a kind, declared or attributed', function (): void {
    foreach (glob(app_path('Notifications/*.php')) ?: [] as $file) {
        $class = 'App\\Notifications\\'.basename($file, '.php');

        if (! is_subclass_of($class, App\Contracts\ExpoPushable::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        $attributed = $reflection->getAttributes(App\Attributes\NotificationKind::class) !== [];

        // A trait's methods report the using class as their declaring class, so an override is
        // told apart from the trait's own kind() by comparing where each is actually defined.
        $traitKind = new ReflectionMethod(App\Notifications\Concerns\PushesToDevices::class, 'kind');
        $classKind = $reflection->hasMethod('kind') ? $reflection->getMethod('kind') : null;
        $overridesKind = $classKind instanceof ReflectionMethod
            && [$classKind->getFileName(), $classKind->getStartLine()] !== [$traitKind->getFileName(), $traitKind->getStartLine()];

        expect($attributed || $overridesKind)->toBeTrue($class);
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
it('keeps App\Concerns down to the Fortify validation traits', function (): void {
    $files = array_map(basename(...), glob(app_path('Concerns/*.php')) ?: []);

    expect($files)->toBe(['PasswordValidationRules.php', 'ProfileValidationRules.php']);
});

// product-invariants.md: execution mode and "I'm overwhelmed" show one step, never a list.
it('keeps every #[OneThing] Data class to one thing', function (): void {
    $config = app(DataConfig::class);

    foreach (glob(app_path('Data/*.php')) ?: [] as $file) {
        $class = 'App\\Data\\'.basename($file, '.php');

        if (new ReflectionClass($class)->getAttributes(OneThing::class) === []) {
            continue;
        }

        expect(violatesOneThing($class, $config))->toBeFalse($class);
    }
});
