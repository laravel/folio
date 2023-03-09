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
     * Replace the given piece of a data with a new piece of data.
     */
    public function replace(string $keyBeingReplaced, string $newKey, mixed $value): MatchedView
    {
        $data = $this->data;

        unset($data[$keyBeingReplaced]);

        $data[$newKey] = $value;

        return new static($this->path, $data);
    }

    /**
     * Create a new matched view instance with the given data.
     */
    public function withData(array $data): MatchedView
    {
        return new static($this->path, $data);
    }
}
