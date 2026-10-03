---
name: upgrade-dependency
description: Upgrade a Composer package this project requires to a new release and carry out what that release asks of its hosts. Use on an "Upgrade <package> to v<x>" issue, "update devtools", "bump <package>", or when devtools:doctor reports devtools outdated.
metadata:
  version: 1.0.0
  gate: claude
  keywords:
    - '\bupgrade\b.{0,40}\bto v\d'
    - '\b(update|upgrade|bump)\b.{0,20}\bdevtools\b'
    - '\bupgrade-dependency\b'
---

# Upgrade Dependency

The issue `Upgrade <package> to v<x.y.z>` names the package and the target. Without one, `release` files them; for a one-off bump, file the issue first with `note-finding`.

## 1. Branch

`start-work #<n>`. Do not wait for `/implement`: the issue is the whole brief, so carry on here.

## 2. Update

```bash
composer show <package> | grep versions   # the installed version, before
composer update <package> -W
```

Add `laravel/boost` to the update when Composer says the new constraint needs it. Check: `composer show <package>` names the target version.

## 3. Sync

For `nvade/devtools` only: `php artisan devtools:sync`, then `php artisan devtools:sync --write` (`vendor/bin/testbench` in a package). Leave edited files alone unless the release asks for them; report each. Check: a second `devtools:sync` reports nothing outdated.

## 4. Do what the release asks

Read `vendor/<package>/CHANGELOG.md` from the old version to the new one. Every item that asks the host to act (a breaking change, a new component to install, a config key to move, a removed package such as `git-guardrails-claude-code`) gets done in this branch, one commit each.

Check: every such item is done, or named in the final message with the reason it was left.

## 5. Finish

`finish-branch`, with `Closes #<n>` in the MR body. Files the package ships unchanged count as no review, so the review covers only what step 4 changed.
