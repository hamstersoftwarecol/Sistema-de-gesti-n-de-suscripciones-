<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * Sellers only see their own records: abort when the model is outside the user's scope.
     */
    protected function ensureVisible(Model $model): void
    {
        $user = request()->user();

        if (! $user || $user->isStaff()) {
            return;
        }

        $visible = $model::query()->visibleTo($user)->whereKey($model->getKey())->exists();

        abort_unless($visible, 403);
    }
}
