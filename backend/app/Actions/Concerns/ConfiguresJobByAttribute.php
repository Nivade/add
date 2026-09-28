<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\CustomAttributes\FailOn;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\FailOnException;
use Illuminate\Support\Traits\ReadsClassAttributes;
use Lorisleiva\Actions\Decorators\JobDecorator;

/** laravel-actions does not read the framework's job attributes, so this trait does. */
trait ConfiguresJobByAttribute
{
    use ReadsClassAttributes;

    public function configureJob(JobDecorator $job): void
    {
        $tries = $this->getAttributeInstance($this, Tries::class);

        if ($tries instanceof Tries) {
            $job->setTries($tries->tries);
        }
    }

    /** @return list<int>|int|null */
    public function getJobBackoff(): array|int|null
    {
        $backoff = $this->getAttributeInstance($this, Backoff::class);

        if (! $backoff instanceof Backoff) {
            return null;
        }

        return is_array($backoff->backoff) ? array_values($backoff->backoff) : $backoff->backoff;
    }

    /** @return list<FailOnException> */
    public function getJobMiddleware(): array
    {
        $failOn = $this->getAttributeInstance($this, FailOn::class);

        return $failOn instanceof FailOn ? [new FailOnException($failOn->exceptions)] : [];
    }
}
