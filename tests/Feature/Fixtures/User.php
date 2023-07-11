<?php

namespace Tests\Feature\Fixtures;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as BaseUser;

class User extends BaseUser
{
    protected $guarded = [];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
