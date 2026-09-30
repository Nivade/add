<?php

declare(strict_types=1);

use App\Models\User;

it('fits home on a phone without scrolling sideways', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/home')->on()->iPhone15();

    $page->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});
