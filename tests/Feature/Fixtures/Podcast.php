<?php

namespace Tests\Feature\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Podcast extends Model
{
    use SoftDeletes;

    protected $table = 'podcasts';
    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
