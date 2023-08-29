<?php

use Illuminate\Contracts\View\View;
use Tests\Feature\Fixtures\User;
use function Laravel\Folio\get;

get(function (View $view, User $user) {
    return $view->with('modelRouteBinding', $user);
})->name('users.show') ?>

<div>
    <span>user variable: {{ $user->name }} </span>
    <span>modelRouteBinding variable: {{ $modelRouteBinding->name }} </span>
</div>
