<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesOwned
{
    /**
     * Someone else's row is not found rather than forbidden: its existence is not theirs to learn.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    protected function owned(Request $request, Model $model): Model
    {
        abort_unless($model->getAttribute('user_id') === $this->user($request)->id, 404);

        return $model;
    }
}
