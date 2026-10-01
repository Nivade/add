<?php

declare(strict_types=1);

use App\Enums\Place;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;

beforeEach(function (): void {
    // The canned driver, not the queued fake: this walks a real browser against
    // the same deterministic answers a person with no API key gets.
    config()->set('ai-toolkit.driver', 'canned');
});

/**
 * What the browser's own focus is on, read the same way a screen reader would —
 * and, per §31, that focus is not just present but rendered: `outline-none` with
 * no `:focus-visible` ring would pass a check that only reads activeElement.
 */
function focusedDescriptor(mixed $page): string
{
    $result = json_decode((string) $page->script(<<<'JS_WRAP'
        (function () {
            var el = document.activeElement;
            if (!el) { return JSON.stringify({label: '', visible: false}); }
            var style = getComputedStyle(el);
            var parked = el.tagName === 'H1' && el.getAttribute('tabindex') === '-1';
            var visible = parked || style.boxShadow !== 'none' || style.outlineStyle !== 'none';
            var copy = el.cloneNode(true);
            copy.querySelectorAll('kbd').forEach(function (kbd) { kbd.remove(); });
            var label = el.getAttribute('aria-label') || (copy.textContent || '').trim();
            return JSON.stringify({label: label, visible: visible});
        })()
    JS_WRAP), associative: true, flags: JSON_THROW_ON_ERROR);

    expect($result['visible'])->toBeTrue('Focus landed on "'.$result['label'].'" with no visible indicator.');

    return $result['label'];
}

/** A button by its accessible name, so the key hint drawn inside it does not change what it is called. */
function button(string $name): string
{
    return 'internal:role=button[name="'.$name.'"]';
}

it('walks the §39 journey by keyboard, with focus visible at every control', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/home');
    $page->assertSee('Nothing needs you right now.')
        ->assertNoJavaScriptErrors();

    // Capture, via the global shortcut rather than a click, and let the app sort it.
    $page->keys('header:first-of-type', 'c');
    $page->assertSee("What's on your mind?");

    expect(focusedDescriptor($page))->toBe("What's on your mind?");

    $page->type('[aria-label="What\'s on your mind?"]', 'waiting for John to send the contract');
    $page->keys('Save', 'Enter');

    $page->assertSee('“waiting for John to send the contract”')
        ->assertSee('Saved as something you are waiting on from John.');

    $page->click("That's right");
    $page->assertDontSee('Saved as something you are waiting on from John.');

    $page->keys('header:first-of-type', 'c');
    $page->type('[aria-label="What\'s on your mind?"]', 'clean the kitchen before my parents arrive');
    $page->keys('Save', 'Enter');

    // One action, and focus handed back to the button that opens the box.
    $page->assertSee('Put the thing you need on the desk.')
        ->assertDontSee('Write it however it comes out.');

    expect(focusedDescriptor($page))->toBe('Capture');

    // Start.
    $page->keys(button('Start'), 'Enter');
    $page->assertPathIs('/focus')
        ->assertSee('Put the thing you need on the desk.');

    expect(focusedDescriptor($page))->toBe('Put the thing you need on the desk.');

    // Done, by its key rather than by reaching the button.
    $page->keys('h1[tabindex]', 'd');

    // The next step, and a progress line nobody wrote by hand.
    $page->assertSee('Open it.')
        ->assertSee('1 of 3 steps done.');

    expect(focusedDescriptor($page))->toBe('Open it.');

    // Distracted, and welcomed back.
    $page->keys(button('I got distracted'), 'Enter');
    $page->assertSee('Welcome back.')
        ->assertSee('You were working on');

    // Focus follows the screen to its one thing, rather than staying on a button that is gone.
    expect(focusedDescriptor($page))->toBe('Welcome back.');

    $page->keys('h1[tabindex]', 'Enter');
    $page->assertSee('Open it.');

    // Paused, which is not a return.
    $page->keys(button('Pause'), 'Enter');
    $page->assertSee('Paused.');

    expect(focusedDescriptor($page))->toBe('Paused.');

    $page->keys(button('Continue'), 'Enter');
    $page->assertSee('Open it.');

    expect(focusedDescriptor($page))->toBe('Open it.');

    // Capture still opens from focus, where the header is gone, and leaves the step where it was.
    $page->keys('h1[tabindex]', 'c');
    $page->type('[aria-label="What\'s on your mind?"]', 'water the plants');
    $page->keys('Save', 'Enter');
    $page->assertDontSee('Write it however it comes out.')
        ->assertSee('Open it.');

    $page->keys('h1[tabindex]', 'd');
    $page->assertSee('Write the first line.')
        ->assertSee('2 of 3 steps done.');

    // Finish, on a closing screen that offers one thing next and asks nothing.
    $page->keys('h1[tabindex]', 'd');
    $page->assertPathContains('/finished')
        ->assertSee('clean the kitchen before my parents arrive is handled.')
        ->assertSee('Next, if you want:')
        ->assertSee('Leave it there');

    $page->keys('h1[tabindex]', 'Escape');
    $page->assertPathIs('/home')
        ->assertNoJavaScriptErrors();
});

it('takes back a guess about where the person is in one tap', function (): void {
    $user = User::factory()->create();

    $errands = Intention::factory()->decomposed()->for($user)->create(['title' => 'Run the errands']);
    Step::factory()->for($errands)->create(['title' => 'Buy bin bags.', 'position' => 1, 'place' => Place::Out, 'estimated_seconds' => 60]);

    $kitchen = Intention::factory()->decomposed()->for($user)->create(['title' => 'Clean the kitchen']);
    Step::factory()->for($kitchen)->create(['title' => 'Wipe one worktop.', 'position' => 1, 'place' => Place::Home, 'estimated_seconds' => 120]);

    finishStepAt($user, Place::Home);

    $this->actingAs($user);

    $page = visit('/home');
    $page->assertSee('Wipe one worktop.')
        ->assertSee('You seem to be at home, where this gets done.');

    $page->click("I'm not at home");

    $page->assertSee('Buy bin bags.')
        ->assertDontSee('You seem to be at home')
        ->assertDontSee("I'm not at home")
        ->assertNoJavaScriptErrors();
});
