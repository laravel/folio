<?php

namespace Laravel\Folio\Pipeline;

class MatchedView
{
    public $path;
    public $data;

    /**
     * Create a new matched view instance.
     */
    public function __construct(string $path, array $data)
    {
        $this->path = realpath($path);
        $this->data = $data;
    }

    /**
     * Create a new matched view instance with the given data.
     */
    public function withData(array $data): MatchedView
    {
        return new static($this->path, $data);
    }
}
