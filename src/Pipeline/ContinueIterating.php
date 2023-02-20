<?php

namespace Laravel\Folio\Pipeline;

class ContinueIterating
{
    public function __construct(public State $state)
    {
    }
}
