<?php

namespace Laravel\Folio\Pipeline;

class ContinueIterating
{
    /**
     * Create a new continue iterating instance.
     */
    public function __construct(public State $state) {}
}
