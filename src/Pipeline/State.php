<?php

namespace Laravel\Folio\Pipeline;

class State
{
    public function __construct(
        public string $uri,
        public string $mountPath,
        public array $segments,
        public array $data = [],
        public int $currentIndex = 0
    )
    {

    }

    /**
     * Create a new state instance for the given iteration.
     */
    public function forIteration(int $iteration): State
    {
        return new static(
            $this->uri,
            $this->mountPath,
            $this->segments,
            $this->data,
            $iteration,
        );
    }

    /**
     * Create a new state instance with the given data added.
     */
    public function withData(string $key, mixed $value): State
    {
        return new static(
            $this->uri,
            $this->mountPath,
            $this->segments,
            array_merge($this->data, [$key => $value]),
            $this->currentIndex,
        );
    }

    /**
     * Get the number of URI segments that are present.
     */
    public function uriSegmentCount(): int
    {
        return count($this->segments);
    }

    /**
     * Get the current URI segment for the given iteration.
     */
    public function currentUriSegment(): string
    {
        return $this->segments[$this->currentIndex];
    }

    /**
     * Replace the segment value for the current iteration.
     */
    public function replaceCurrentUriSegmentWith(string $value): State
    {
        $segments = $this->segments;

        $segments[$this->currentIndex] = $value;

        return new static(
            $this->uri,
            $this->mountPath,
            $segments,
            $this->data,
            $this->currentIndex,
        );
    }

    /**
     * Determine if the current iteration is for the last segment.
     */
    public function onLastUriSegment(): bool
    {
        return $this->currentIndex === ($this->uriSegmentCount() - 1);
    }

    /**
     * Get the absolute path to the current directory for the given iteration.
     */
    public function currentDirectory(): string
    {
        return $this->mountPath.'/'.implode('/', array_slice($this->segments, 0, $this->currentIndex));
    }
}
