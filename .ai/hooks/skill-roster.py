#!/usr/bin/env python3
"""UserPromptSubmit: the repo's skill roster once per session, then keyword hits as they come up."""

import hashlib
import json
import os
import re
import sys
import tempfile

ROSTER = """This repo's own skills, by what they own — invoke before working, not after getting stuck:
sail-and-root-scripts (any command), generated-artifacts (files a generator owns), slice-workflow (what to build),
next-action-resolver (the ranking engine), ai-layer-changes (anything asking a model), calendar-sync, expo-react-native,
phpstan-larastan, laravel-actions (app/Actions), laravel-data (app/Data), laravel-attributes, inertia-react-development,
wayfinder-development, pest-testing and testing-best-practices, note-finding, update-resume, finish-branch
(ready to merge — code-review, then simplify, then the PR; merge-gate enforces it).
Expo's own skills are vendored for the platform mechanics this repo does not author: expo-router (mobile/app),
expo-data-fetching (mobile/src/api), expo-dev-client, expo-upgrade, eas-workflows. expo-react-native still
wins on house convention — what belongs in packages/shared, and never reusing an Inertia component.
Path-scoped rules live in .ai/rules/index.md and bind before any of them."""

# Keyword -> skills the prompt implies, before any file is opened.
KEYWORDS = [
    (r"\b(test|tests|pest|suite|coverage|failing)\b", ["pest-testing", "testing-best-practices"]),
    (r"\b(artisan|composer|sail|migrate|npm run|install|lint|pint|container)\b", ["sail-and-root-scripts"]),
    (r"\b(phpstan|stan|larastan|type error|level 7)\b", ["phpstan-larastan"]),
    (r"\b(prompt|schema|fixture|decompose|llm|model answer|openai|ai layer)\b", ["ai-layer-changes"]),
    (r"\b(next action|ranking|which step|resolver|why string|prioriti[sz])\b", ["next-action-resolver"]),
    (r"\b(calendar|appointment|ics|sync)\b", ["calendar-sync"]),
    (r"\b(mobile|expo|react native|native app|push)\b", ["expo-react-native"]),
    (r"\b(expo router|deep link|navigation|native tab|form sheet|file.based rout)\b", ["expo-router"]),
    (r"\b(dev client|development build|simulator|emulator|testflight|prebuild)\b", ["expo-dev-client"]),
    (r"\b(sdk \d+|upgrade expo|expo upgrade|new architecture|react compiler)\b", ["expo-upgrade"]),
    (r"\b(eas|workflow yaml|app store|submit the app)\b", ["eas-workflows"]),
    (r"\b(react query|swr|offline|useloaderdata|loader)\b", ["expo-data-fetching"]),
    (r"\b(generated|regenerate|types:generate|wayfinder|boost:update)\b", ["generated-artifacts"]),
    (r"\b(slice|spec|plan|scope|what.s next|roadmap)\b", ["slice-workflow"]),
    (r"\b(data object|dto|laravel-data|generated\.ts)\b", ["laravel-data"]),
    (r"\b(action|use.case|handle\(\))\b", ["laravel-actions"]),
    (r"\b(component|page|inertia|react(?! native)|tailwind|ui)\b", ["inertia-react-development", "tailwindcss-development"]),
    (r"\b(route|controller|endpoint|api)\b", ["wayfinder-development"]),
    (r"\b(auth|login|register|passkey|two.factor|fortify|sanctum)\b", ["fortify-development", "laravel-security"]),
    (r"\b(migration|model|eloquent|table|column)\b", ["laravel-attributes"]),
    (r"\b(ready to merge|finish (this|the) branch|open (a |the )?pr\b|open the pull request)\b", ["finish-branch"]),
    (r"\b(commit|pr |pull request|review)\b", ["code-review", "split-to-prs"]),
    (r"\b(wrap up|stopped|resume|end of session)\b", ["update-resume"]),
]


def emit(context):
    if context:
        print(json.dumps({"hookSpecificOutput": {
            "hookEventName": "UserPromptSubmit",
            "additionalContext": context,
        }}))
    sys.exit(0)


def fired(session):
    path = os.path.join(tempfile.gettempdir(), "claude-skill-roster-" + hashlib.sha256(session.encode()).hexdigest()[:16])
    known = set()
    if os.path.exists(path):
        with open(path) as handle:
            known = set(handle.read().split("\n"))
    return path, known


def main():
    try:
        event = json.load(sys.stdin)
    except (ValueError, OSError):
        emit(None)

    prompt = (event.get("prompt") or "").lower()
    path, known = fired(str(event.get("session_id", "none")))

    parts = []
    if "__roster__" not in known:
        parts.append(ROSTER)
        known.add("__roster__")

    hits = []
    for pattern, skills in KEYWORDS:
        if re.search(pattern, prompt):
            hits += [skill for skill in skills if skill not in known]
    hits = list(dict.fromkeys(hits))

    if hits:
        known.update(hits)
        parts.append("This prompt points at: " + ", ".join(hits) + ". Invoke what applies before acting.")

    with open(path, "w") as handle:
        handle.write("\n".join(sorted(known)))

    emit("\n".join(parts) if parts else None)


main()
