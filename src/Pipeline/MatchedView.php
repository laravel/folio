<?php

namespace Laravel\Folio\Pipeline;

class MatchedView
{
    public function __construct(public string $path, public array $data)
    {
    }
}
