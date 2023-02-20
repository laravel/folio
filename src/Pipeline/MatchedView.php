<?php

namespace Laravel\Folio\Pipeline;

class MatchedView
{
    public $path;
    public $data;

    public function __construct(string $path, array $data)
    {
        $this->path = realpath($path);
        $this->data = $data;
    }
}
