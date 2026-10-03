---
name: laravel-attributes
description: "Use whenever writing, reviewing or migrating Laravel PHP code (Laravel 13+). Prefer PHP attributes over class properties for models, jobs, commands, controllers, form requests, tests, factories, API resources and container bindings. Trigger on $fillable, $hidden, $table, $queue, $tries, $signature, $redirect, $errorBag, $seeder, constructor middleware, singleton registration, and any new Laravel class."
paths:
  - 'backend/app/**'
  - 'backend/database/factories/**'
  - 'backend/database/seeders/**'
metadata:
  keywords:
    - '\$(fillable|hidden|guarded|appends|touches|table|connection|queue|tries|timeout|backoff|signature|redirectTo|errorBag)\b'
    - '\bPHP attributes?\b'
---

# Laravel PHP Attributes (Laravel 13+)

Declare configuration above the class with an attribute instead of a class property. `LARAVEL_130` in `rector.php` enforces most of these, so a leftover property shows up in `composer refactor:check`.

## Rules

1. **Never mix an attribute with its legacy property on one class.** `#[Fillable('name')]` plus `protected $fillable = [...]` leaves Laravel ignoring one of them without an error. Convert the whole class in one pass and delete the property.
2. **Always import the attribute.** Without the `use` line it is a plain unknown attribute and does nothing.
3. **There is no `#[Casts]`.** Casts stay in the `casts()` method.
4. **A container attribute only works on a class the container resolves.** `#[Singleton]` on a class you `new` yourself does nothing.
5. **Check the name against the installed framework**, not memory: `find backend/vendor/laravel/framework/src -path '*Attributes*' -name '*.php'`. Most take variadic or array arguments, so `#[Fillable('a', 'b')]` and `#[Fillable(['a', 'b'])]` both work.

## Where each one lives

| Area | Attributes | Namespace |
| --- | --- | --- |
| Eloquent model | `Table` `Connection` `Fillable` `Guarded` `Unguarded` `Hidden` `Visible` `Appends` `Touches` `WithoutTimestamps` `WithoutIncrementing` `DateFormat` `RouteKey` | `Illuminate\Database\Eloquent\Attributes` |
| Eloquent wiring | `ObservedBy` `ScopedBy` `UseFactory` `UsePolicy` `CollectedBy` `UseResource` `UseEloquentBuilder` | `Illuminate\Database\Eloquent\Attributes` |
| Queue job | `Connection` `Queue` `Tries` `Timeout` `Backoff` `MaxExceptions` `FailOnTimeout` `UniqueFor` `Delay` `DebounceFor` `DeleteWhenMissingModels` `WithoutRelations` | `Illuminate\Queue\Attributes` |
| Console command | `Signature` `Description` `Aliases` `Help` `Usage` | `Illuminate\Console\Attributes` |
| Controller | `Middleware` `WithoutMiddleware` `Authorize`, also valid on an action method | `Illuminate\Routing\Attributes\Controllers` |
| Form request | `RedirectTo` `RedirectToRoute` `ErrorBag` `StopOnFirstFailure` `FailOnUnknownFields` | `Illuminate\Foundation\Http\Attributes` |
| API resource | `Collects` `PreserveKeys` | `Illuminate\Http\Resources\Attributes` |
| Factory | `UseModel` | `Illuminate\Database\Eloquent\Factories\Attributes` |
| Test class | `Seed` `Seeder` `UnitTest` | `Illuminate\Foundation\Testing\Attributes` |
| Container | `Singleton` `Scoped` `Bind` `BindWhen` `Give` `Tag`, plus the injection ones: `Config` `Auth` `Cache` `Log` `DB` `Storage` `CurrentUser` | `Illuminate\Container\Attributes` |

## Examples

```php
use Illuminate\Database\Eloquent\Attributes\{Table, Fillable, Hidden};

#[Table('users')]
#[Fillable('name', 'email')]
#[Hidden('password')]
class User extends Model {}
```

```php
use Illuminate\Queue\Attributes\{Connection, Queue, Tries, Timeout, Backoff};

#[Connection('redis')]
#[Queue('orders')]
#[Tries(3)]
#[Timeout(60)]
#[Backoff(30)]
class ProcessOrder implements ShouldQueue {}
```

```php
use Illuminate\Console\Attributes\{Signature, Description};

#[Signature('users:sync {--force}')]
#[Description('Sync users from the external API')]
class SyncUsers extends Command {}
```

```php
use Illuminate\Routing\Attributes\Controllers\{Middleware, Authorize};

#[Middleware('auth')]
class PostController
{
    #[Authorize('update', 'post')]
    public function update(Post $post) {}
}
```

```php
use Illuminate\Foundation\Http\Attributes\{RedirectToRoute, StopOnFirstFailure, ErrorBag};

#[RedirectToRoute('profile.edit')]
#[StopOnFirstFailure]
#[ErrorBag('updateProfile')]
class UpdateProfileRequest extends FormRequest {}
```

```php
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class StripeGateway implements PaymentGateway {}
```

PHP's own attributes belong here too: `#[\SensitiveParameter]` keeps a value out of stack traces, and `#[\NoDiscard]` warns when a return value is dropped (PHP 8.5).
