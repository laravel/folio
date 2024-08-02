<?php

namespace Laravel\Folio\Events;

use Laravel\Folio\MountPath;
use Laravel\Folio\Pipeline\MatchedView;

class ViewMatched
{
    /**
     * Create a new view matched event instance.
     */
    public function __construct(
        public MatchedView $matchedView,
        public MountPath $mountPath,
    ) {}
}
