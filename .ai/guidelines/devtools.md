## Running the checks

The Composer scripts are the entry points. Call them rather than the tools underneath, since they carry the flags
this project relies on.

- While working: `composer lint:dirty` formats only the files git sees as changed.
- For a quick test run: `composer test:impact` runs just the tests the changes can affect.
- Before pushing: `composer test`. It checks Pint, PHPStan and Rector, then runs the full suite: the same as CI.
- `composer refactor` applies Rector's changes and `composer lint` formats everything. Both rewrite files, so read
  the diff afterwards.
- A Claude Code hook already runs Pint on each PHP file an agent edits, so formatting a single file by hand is
  unnecessary.

## Where the app runs

With a `compose.yaml` in the app root, the app runs in Sail containers. Run PHP through Sail
(`vendor/bin/sail artisan …`, `vendor/bin/sail composer …`): the host PHP cannot reach the database or the other
services. Without one, use the host's `php` and `composer` directly.

Behind Traefik, with `APP_SLUG` and `BASE_DOMAIN` taken from `.env`:

- The app: `https://<APP_SLUG>.<BASE_DOMAIN>`
- Mail the app sends is caught by Mailpit: `https://mailpit.<APP_SLUG>.<BASE_DOMAIN>`
- If `compose.yaml` has a `laravel.debug` service, it serves `https://debug.<APP_SLUG>.<BASE_DOMAIN>`. Debugbar and
  Telescope only run there, so inspect queries and requests on that host.
