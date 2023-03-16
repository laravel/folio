<?php

namespace Tests\Feature\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';
    protected $guarded = [];

    public function podcast()
    {
        return $this->belongsTo(Podcast::class, 'podcast_id');
    }
}
