# Executive Function OS — product spec

The prompt the repo was created from, verbatim. Source of truth for product
intent; `executive-function-os.md` is the build plan derived from it and records
where the build deliberately deviates.

Section numbers are anchors — the slice plans cite them. Edit the text here only
to correct a transcription error, and never renumber. A slice plan references a
section rather than restating it, so that the two cannot drift.

---

## 1. Product vision

Build an application designed specifically for people who struggle with executive function, particularly ADHD.

This is **not another traditional task manager**.

The product's job is to reduce the amount of executive function required from the user.

The central product principle is:

> **The user should never have to figure out what to do next when the application can figure it out for them.**

The application should help users move through:

**Intention → understanding → next action → execution → completion**

rather than simply:

**Task → checkbox**

The product should act as an **external executive-function system**: remembering things, identifying what matters, breaking down overwhelming goals, surfacing the right thing at the right time, helping the user start, recovering them after distraction, and adapting to their actual behavior.

---

# 2. Core principles

### 2.1 Reduce cognitive load

Every screen and interaction should answer:

> "What does the user need to think about right now?"

Avoid unnecessarily exposing:

* complicated project structures
* large task lists
* configuration
* excessive metadata
* prioritization systems
* unnecessary choices

### 2.2 One useful action at a time

When the user enters an execution flow, the application should aggressively reduce distractions.

Instead of:

> 17 tasks remaining

show:

> **Here's the next thing to do.**

### 2.3 Recovery, not punishment

Distraction and abandonment are expected behavior.

Never make the user feel like they have failed because they stopped working.

For example:

> **Welcome back.**
>
> You were cleaning the kitchen.
>
> Want to continue?

Not:

> ❌ Task abandoned

### 2.4 Don't become another source of noise

Notifications should be contextual and valuable.

The application should prefer:

> "You're leaving for the dentist in 30 minutes. Your insurance card is still at home."

over:

> "Reminder: Dentist appointment."

### 2.5 User agency

The system can recommend, prioritize, decompose and remind, but the user remains in control.

Never silently create commitments or perform consequential actions without appropriate confirmation.

---

# 3. Target platforms

Build a single product with:

### Backend

* Laravel
* PHP
* REST/JSON API where appropriate
* Laravel authentication
* queues/jobs
* events
* notifications
* scheduled jobs
* database-backed domain model

### Web

* Laravel + Inertia.js
* React
* TypeScript
* Tailwind CSS
* mobile-responsive web interface

### Mobile

* React Native
* TypeScript
* iOS and Android
* touch-first interaction
* push notifications
* deep links
* native capabilities where useful

The backend must be the authoritative source of truth.

The web and mobile applications should consume the same domain/API layer rather than implementing separate business logic.

---

# 4. Product terminology

Use these concepts throughout the domain model.

### Intention

Something the user wants or needs to accomplish.

Examples:

* Move house
* Clean apartment
* Sort taxes
* Get passport renewed

An intention may contain many tasks.

### Project

A larger outcome containing multiple related actions.

### Task

A discrete unit of work.

### Next Action

The smallest meaningful physical action the user can perform immediately.

Example:

> "Renew passport"

becomes:

> "Find your current passport."

### Execution Session

A focused period in which the application guides the user through work.

### Commitment

Something the user has agreed to do, whether explicitly or implicitly.

### Waiting For

Something dependent on another person or organization.

### Context

Environmental information that affects whether an action makes sense.

Examples:

* At home
* At work
* At supermarket
* On computer
* With phone
* Outside

### Time Constraint

A real temporal constraint.

Examples:

* Appointment at 14:00
* Renewal deadline October 14
* Leave home by 13:30

Do not confuse deadlines with arbitrary task priorities.

---

# 5. Home screen

The home screen should **not** primarily be a task list.

It should answer:

> **What should I do right now?**

Example:

---

## Good evening

You have 47 things in your system.

You don't need to think about them.

### Right now

**Put the laundry in the washing machine.**

~3 minutes

[ Start ]

---

Additional information may appear below:

### Coming up

Dentist
Tomorrow · 14:00

### Needs attention

Car insurance
Renewal required in 26 days

### Waiting for

Insurance company
Claim response

---

The exact design is flexible, but the conceptual hierarchy is important:

1. Current action
2. Immediate context
3. Important upcoming things
4. Everything else

---

# 6. Quick capture

The application must make capturing information extremely easy.

Users should be able to capture:

* text
* voice
* photo
* document
* email
* URL
* screenshot
* calendar event
* arbitrary thought

Examples:

> "Need to buy dishwasher tablets"

> "I need to call the dentist"

> "Remember that Sarah owes me €30"

> [photo of letter]

> [forward email]

Capture should **not require the user to categorize the item**.

The system can classify it later.

The user should be able to dump something into the system and move on.

---

# 7. Natural language capture

The user should be able to say:

> "I need to clean the apartment before Saturday because my parents are coming."

The system should extract:

* intention: clean apartment
* deadline: Saturday
* reason/context: parents visiting
* likely project/task structure

Do not force users to manually fill forms.

Allow:

> "Remind me tomorrow morning to call the dentist."

to become an appropriate reminder.

Allow:

> "I should probably renew my passport."

to become an intention requiring clarification rather than immediately becoming a rigid task.

---

# 8. Intelligent task decomposition

Large intentions should automatically be decomposed into manageable actions.

Example:

### Intention

> Move apartment

Potential decomposition:

1. Confirm moving date
2. Find moving company
3. Request quotes
4. Book moving company
5. Notify landlord
6. Arrange utilities
7. Change address
8. Pack kitchen
9. Pack bedroom
10. etc.

But the execution interface should **not dump all ten tasks onto the user**.

Instead:

> **Next action**
>
> Find your current rental contract.
>
> [ Start ]

The system should progressively expose complexity only when necessary.

---

# 9. Next-action engine

Build a domain service responsible for determining the user's most useful next action.

The engine should consider:

* deadlines
* appointments
* dependencies
* estimated duration
* location
* available tools
* current time
* user's stated intentions
* task urgency
* task importance
* whether something is blocked
* user's recent behavior
* current execution session
* energy/time constraints where explicitly provided
* whether the task has repeatedly been avoided

Avoid reducing everything to a simplistic numeric priority score.

The goal is not:

> "Task #183 has priority 87."

The goal is:

> **"Given everything currently known, this is probably the most useful thing to do next."**

The recommendation should remain explainable.

Example:

> **Why this?**
>
> Your appointment is tomorrow and this takes about 5 minutes.

---

# 10. Execution mode

Execution mode is a core feature.

When a user starts an action, the application enters a focused state.

Example:

## Clean kitchen

### Step 1

**Grab a trash bag.**

[ Done ]

---

Then:

### Step 2

**Put the obvious trash into the bag.**

~5 minutes

[ Done ]

---

The application controls the flow.

Do not show the entire project unless the user explicitly asks.

The user should always have:

* Done
* Skip
* Pause
* I'm stuck
* I got distracted
* Stop

---

# 11. "I'm stuck"

This must be a first-class interaction.

If the user presses:

> **I'm stuck**

the application should help determine why.

Possible responses:

> What's blocking you?

* I don't know what to do
* This is too much
* I need something
* I don't have enough information
* I'm tired
* I don't want to do it
* Something else

The system should then adapt.

For example:

> **This feels too big.**

Response:

> Let's make it smaller.
>
> Forget the whole kitchen.
>
> **Pick up one thing from the floor.**

---

# 12. "I got distracted"

This should be easily accessible during execution.

When activated:

> **Welcome back.**
>
> You were working on:
>
> **Clean kitchen**
>
> You had completed 3 steps.
>
> [ Continue ]

Optionally record the interruption.

The goal is recovery, not behavioral judgment.

---

# 13. Time blindness support

The application should provide contextual time information.

Avoid relying exclusively on countdown timers.

Examples:

> You have about 35 minutes before you need to leave.

> If you start getting dressed now, you'll have about 10 minutes to spare.

> You've been working for 12 minutes.

> This task usually takes you around 15 minutes.

For appointments, calculate backwards:

**Appointment: 14:00**

* Leave: 13:30
* Get ready: 13:10
* Find required items: 13:00

The system should make these assumptions visible and editable.

---

# 14. Context-aware reminders

Reminders should contain useful context.

Bad:

> Dentist appointment tomorrow.

Better:

> Dentist tomorrow at 14:00.
>
> You need to leave around 13:30.
>
> Your insurance card is still at home.

Context can include:

* location
* calendar
* required documents
* objects
* dependencies
* preparation steps
* estimated travel time where available

---

# 15. Future-self system

Allow users to leave instructions for their future selves.

Example:

> "Tomorrow when I leave work, remind me to buy dishwasher tablets."

The application should associate the reminder with:

* time
* location
* calendar context
* relevant task

Potential reminder:

> You're leaving work.
>
> You wanted to buy dishwasher tablets on the way home.

---

# 16. Anti-overwhelm mode

A user should be able to press:

> **I'm overwhelmed**

The application should temporarily simplify their world.

Example:

> You've got a lot going on.
>
> Ignore everything else for now.
>
> **Let's do one thing.**
>
> Put the dishes in the dishwasher.
>
> ~4 minutes
>
> [ Start ]

Do not present the user with their entire backlog.

---

# 17. Body doubling

Introduce optional body-doubling functionality.

Possible modes:

### Solo

A guided execution session.

### Anonymous group

Users work simultaneously without direct interaction.

### Friend

Invite another person into a session.

### AI companion

A lightweight conversational presence during work.

The system should not become socially addictive.

The objective is providing structure and accountability.

---

# 18. Dopamine and progress

Use progress feedback carefully.

Avoid manipulative gamification.

Useful feedback:

> You completed 4 things today that you've been putting off.

> You started this task after avoiding it for 11 days.

> Your project is now 60% complete.

> You finished the hardest part.

Do not create endless XP systems, streak anxiety or punishment for inactivity unless explicitly chosen by the user.

---

# 19. Life administration integration

The application should eventually ingest external information.

Potential sources:

* email
* calendar
* documents
* scanned mail
* receipts
* notifications
* bank-related documents
* subscriptions
* bills
* government correspondence

The system can detect:

> "This looks like something you need to act on."

Example:

**Email**

> Your car insurance expires October 14.

System:

> 🚗 **Car insurance renewal**
>
> Due October 14.
>
> Estimated effort: ~10 minutes.
>
> Next action:
>
> **Open your current insurance policy.**
>
> [ Start ]

This should be designed as a modular ingestion architecture rather than tightly coupling the core task system to one provider.

---

# 20. Waiting-for system

A major source of executive-function failure is forgetting things that are not currently actionable.

Support:

> Waiting for: John to send contract

The system can later surface:

> John hasn't sent the contract yet.

The user can decide:

* Wait longer
* Follow up
* Cancel
* Mark received

---

# 21. Commitments

Detect explicit commitments.

Examples:

> "I'll send that tomorrow."

> "I'll call you Friday."

> "I'll bring the documents."

These should potentially become commitments.

The system should distinguish:

* user explicitly created task
* user explicitly committed
* system inferred potential commitment

Inferred information should be clearly marked and never silently treated as fact.

---

# 22. Calendar integration

Calendar events should become part of the execution system.

Instead of merely displaying:

> Dentist — 14:00

the application should reason about preparation.

Example:

**Dentist — 14:00**

Preparation:

* Find insurance card
* Bring medication information
* Leave home at 13:30

The user should be able to override all generated preparation.

---

# 23. Notifications

Notifications should be:

* contextual
* actionable
* sparse
* configurable
* intelligently timed

Avoid notification spam.

A notification should ideally answer:

1. Why am I being interrupted?
2. What do I need to do?
3. Why now?
4. What happens if I don't?

---

# 24. Architecture

Use a modular Laravel architecture.

Suggested domains:

```text
App/
    Domain/
        Users/
        Tasks/
        Intentions/
        Projects/
        Actions/
        Execution/
        Commitments/
        WaitingFor/
        Calendar/
        Notifications/
        Capture/
        Ingestion/
        Context/
        Recommendations/
        Documents/
```

Avoid creating an overly elaborate architecture before it is necessary.

Prefer well-defined domain services, actions and value objects over massive controllers or models containing business logic.

---

# 25. Core entities

At minimum consider:

```text
User
Intention
Project
Task
Action
ExecutionSession
ExecutionStep
Commitment
WaitingFor
Reminder
Context
Capture
Document
CalendarEvent
Notification
Activity
```

The final schema should be determined during implementation based on actual requirements.

Avoid premature normalization and unnecessary abstractions.

---

# 26. Recommendation engine

Create a replaceable recommendation layer.

For example:

```php
interface NextActionResolver
{
    public function resolve(User $user, Context $context): ?Action;
}
```

The initial implementation should be deterministic where possible.

Use AI only where it materially improves the experience.

Potential AI use cases:

* natural-language capture
* task decomposition
* ambiguous intent interpretation
* email/document interpretation
* generating helpful explanations
* identifying potential commitments
* conversational assistance

Do not use AI for deterministic operations that can be solved reliably with normal application code.

---

# 27. AI architecture

AI should be an enrichment layer rather than the foundation of the application.

Create interfaces around AI functionality so providers/models can be replaced.

For example:

```text
Ai/
    IntentParser
    TaskDecomposer
    CommitmentDetector
    DocumentInterpreter
    EmailInterpreter
```

Do not scatter model/API calls throughout controllers and Livewire/React components.

AI operations should be:

* queued where appropriate
* observable
* retryable
* auditable
* cancellable where practical
* privacy-conscious

---

# 28. Privacy

This application will potentially contain extremely sensitive personal information.

Treat privacy as a first-class architectural concern.

Requirements:

* explicit integrations
* clear consent
* provider disclosure
* data retention controls
* deletion
* audit logging
* encryption where appropriate
* minimal data collection
* least-privilege access
* secure secrets management
* clear distinction between user data and derived AI data

Never send information to an external AI provider without an intentional architectural path allowing that behavior to be controlled.

---

# 29. Web UX

Use:

* React
* TypeScript
* Inertia
* Tailwind

The web application should prioritize:

* keyboard interaction
* large clear actions
* minimal visual clutter
* responsive layouts
* fast navigation
* accessible controls

Primary screens:

```text
Home
Capture
Focus / Execution
Projects
Calendar
Inbox
Waiting For
Commitments
Search
Settings
```

Avoid making all of these equally prominent in navigation.

The Home/Focus experience should remain the primary workflow.

---

# 30. Mobile UX

React Native should be designed specifically for touch.

Do not simply reproduce the web UI.

Prioritize:

* one-handed interaction
* large touch targets
* quick capture
* voice capture
* camera/document capture
* push notifications
* location/context triggers where explicitly enabled
* deep links
* lock-screen/notification actions where platform capabilities permit
* fast startup

A mobile user should be able to capture a thought in seconds.

---

# 31. Accessibility

Treat accessibility as a core requirement.

Support:

* keyboard navigation
* screen readers
* semantic controls
* adequate contrast
* scalable text
* reduced motion
* clear focus states
* large touch targets
* non-color-only indicators

Avoid unnecessarily dense interfaces.

---

# 32. MVP

Do not attempt to build the entire vision initially.

The first useful product should contain:

### Capture

* quick text capture
* natural language input

### Intentions

* create intention
* convert intention into tasks/actions

### Next Action

* deterministic next-action selection
* one recommended action

### Execution

* focused execution mode
* step-by-step actions
* Done
* Skip
* Pause
* I'm stuck
* I got distracted

### Anti-overwhelm

* "I'm overwhelmed"
* temporarily reduce the user's visible workload

### Time

* basic deadlines
* calendar integration
* preparation/reminder logic

### Home

* current recommended action
* upcoming commitments
* things requiring attention

Build these exceptionally well before adding integrations.

---

# 33. Phase 2

Add:

* email ingestion
* document ingestion
* waiting-for
* commitments
* contextual reminders
* future-self reminders
* recurring tasks
* richer calendar integration
* voice capture
* mobile push notifications
* body doubling

---

# 34. Phase 3

Potentially add:

* AI companion
* anonymous body doubling
* advanced context awareness
* location-aware reminders
* subscription detection
* bill detection
* automatic life-admin workflows
* proactive identification of forgotten commitments
* deeper email/calendar automation

---

# 35. Important UX anti-patterns

Do NOT build:

### A giant dashboard

The user shouldn't need to interpret 15 widgets.

### A complicated priority matrix

The application should do more of the thinking.

### Infinite notifications

More reminders do not solve remembering.

### Shame mechanics

No:

> "You haven't completed your tasks."

Prefer:

> "Want to pick this back up?"

### Mandatory categorization

Capture first. Organize later.

### Excessive configuration

Defaults should work.

### AI everywhere

Use deterministic software where deterministic software is better.

### Productivity theater

Do not optimize for users spending more time inside the application.

Optimize for:

> **The user getting their life handled and leaving the application.**

---

# 36. Success metrics

Do not primarily measure:

* daily active users
* sessions per day
* time spent in app
* number of notifications opened

Instead measure outcomes such as:

* percentage of captured intentions successfully completed
* time from intention → first action
* percentage of sessions resulting in meaningful progress
* abandoned-task recovery rate
* percentage of overdue items successfully recovered
* number of commitments successfully fulfilled
* user-reported reduction in overwhelm
* user-reported reduction in remembering things manually

A successful user should arguably spend **less** time in the application over time.

---

# 37. Development philosophy

Build the product as a serious production application, not a prototype disguised as one.

Requirements:

* strong typing
* automated tests
* static analysis
* formatting
* clear domain boundaries
* robust authorization
* feature tests for important workflows
* API tests
* frontend component tests where valuable
* end-to-end tests for critical user journeys
* migrations that work across supported environments
* queues for expensive work
* proper observability

Avoid unnecessary abstractions.

Prefer simple code that is easy to understand and change.

---

# 38. First implementation task

Before writing substantial application code:

1. Inspect the existing Laravel project.
2. Establish the current Laravel/PHP versions and installed packages.
3. Inspect the existing frontend/tooling.
4. Determine whether the existing authentication system can be retained.
5. Propose the initial domain model.
6. Propose the API boundaries consumed by React Native and Inertia.
7. Identify architectural risks.
8. Identify what should be deterministic versus AI-powered.
9. Produce an implementation plan divided into small vertical slices.
10. Only then begin implementation.

Do not rewrite the project unnecessarily.

Preserve useful existing infrastructure.

---

# 39. First vertical slice

The first complete user journey should be:

```text
User opens app
        ↓
"I need to clean my apartment"
        ↓
System creates intention
        ↓
System proposes manageable actions
        ↓
User sees ONE next action
        ↓
User presses Start
        ↓
Execution mode begins
        ↓
User completes action
        ↓
System presents next action
        ↓
User gets distracted
        ↓
User presses "I got distracted"
        ↓
System welcomes them back
        ↓
User continues
        ↓
Intention progresses
        ↓
User finishes
        ↓
System provides useful progress feedback
```

This journey should feel exceptionally polished before expanding the product.

---

# 40. Product north star

The application should feel like having a calm, competent person sitting beside you who says:

> **"Don't worry about everything else. Here's what we're doing right now."**

It should remember what the user forgets.

It should break down what feels overwhelming.

It should notice when something needs attention.

It should help the user start.

It should help them recover after distraction.

It should handle complexity without making the user manage that complexity.

And ultimately:

> **The app should make the user's life easier, not become another thing they have to manage.**
