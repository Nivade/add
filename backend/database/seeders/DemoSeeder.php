<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StepStatus;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/** A world with one thing worth doing, one deadline and one thing nobody could name, for clicking through. */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo',
            'email' => 'demo@add.test',
            'email_verified_at' => now(),
        ]);

        $kitchen = Intention::factory()->decomposed()->for($user)->create([
            'title' => 'Clean the kitchen',
            'why' => 'Parents are coming',
        ]);

        $this->steps($kitchen, [
            ['Grab a bin bag.', 60],
            ['Put the obvious rubbish in the bag.', 300],
            ['Move every dirty dish to one side of the sink.', 180],
            ['Fill the dishwasher.', 300],
        ]);

        $passport = Intention::factory()->decomposed()->for($user)->create([
            'title' => 'Renew the passport',
            'deadline_at' => CarbonImmutable::now()->addDays(9),
        ]);

        $this->steps($passport, [
            ['Find the old passport.', 240],
            ['Photograph the front page.', 120],
        ]);

        Intention::factory()->unclear()->for($user)->create(['title' => 'Sort the thing out']);

        Intention::factory()->count(11)->for($user)->create();
    }

    /** @param  list<array{0: string, 1: int}>  $steps */
    private function steps(Intention $intention, array $steps): void
    {
        foreach ($steps as $position => [$title, $seconds]) {
            Step::factory()->for($intention)->create([
                'title' => $title,
                'position' => $position + 1,
                'estimated_seconds' => $seconds,
                'status' => StepStatus::Pending,
            ]);
        }
    }
}
