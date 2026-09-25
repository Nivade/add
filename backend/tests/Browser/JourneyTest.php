<?php

declare(strict_types=1);

use App\Contracts\AiProvider;
use App\Models\User;

beforeEach(function (): void {
    // The canned driver, not the queued fake: this walks a real browser against
    // the same deterministic answers a person with no API key gets.
    config()->set('ai.driver', 'canned');
    app()->forgetInstance(AiProvider::class);
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
            var visible = style.boxShadow !== 'none' || style.outlineStyle !== 'none';
            var label = el.getAttribute('aria-label') || (el.textContent || '').trim();
            return JSON.stringify({label: label, visible: visible});
        })()
    JS_WRAP), associative: true, flags: JSON_THROW_ON_ERROR);

    expect($result['visible'])->toBeTrue('Focus landed on "'.$result['label'].'" with no visible indicator.');

    return $result['label'];
}

it('walks the §39 journey by keyboard, with focus visible at every control', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/home');
    $page->assertSee('Nothing needs you right now.')
        ->assertNoJavaScriptErrors();

    // Capture, via the global shortcut rather than a click.
    $page->keys('nav[aria-label="Main"]', 'c');
    $page->assertSee("What's on your mind?");
    expect(focusedDescriptor($page))->toBe("What's on your mind?");

    $page->type('[aria-label="What\'s on your mind?"]', 'clean the kitchen before my parents arrive');
    $page->keys('Capture', 'Enter');
    expect(focusedDescriptor($page))->toBe('Capture');

    // One action.
    $page->assertSee('Put the thing you need on the desk.');

    // Start.
    $page->keys('Start', 'Enter');
    expect(focusedDescriptor($page))->toBe('Start');
    $page->assertPathIs('/focus')
        ->assertSee('Put the thing you need on the desk.');

    // Done.
    $page->keys('Done', 'Enter');
    expect(focusedDescriptor($page))->toBe('Done');

    // The next step, and a progress line nobody wrote by hand.
    $page->assertSee('Open it.')
        ->assertSee('1 of 3 steps done.');

    // Distracted.
    $page->keys('I got distracted', 'Enter');
    expect(focusedDescriptor($page))->toBe('I got distracted');
    $page->assertSee('Open it.');

    $page->keys('Pause', 'Enter');
    expect(focusedDescriptor($page))->toBe('Pause');
    $page->assertSee('Welcome back.');

    // Welcome back.
    $page->keys('Continue', 'Enter');
    expect(focusedDescriptor($page))->toBe('Continue');
    $page->assertSee('Open it.');

    $page->keys('Done', 'Enter');
    $page->assertSee('Write the first line.')
        ->assertSee('2 of 3 steps done.');

    // Finish.
    $page->keys('Done', 'Enter');
    $page->assertPathIs('/home')
        ->assertSee('Nothing needs you right now.')
        ->assertNoJavaScriptErrors();
});
