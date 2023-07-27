<?php

namespace Laravel\Folio\Pipeline;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Folio\Folio;

trait FindsWildcardViews
{
    /**
     * Attempt to find a wildcard multi-segment view at the given directory.
     */
    protected function findWildcardMultiSegmentView(string $directory): ?string
    {
        return $this->findViewWith($directory, '[...', ']');
    }

    /**
     * Attempt to find a wildcard view at the given directory.
     */
    protected function findWildcardView(string $directory): ?string
    {
        return $this->findViewWith($directory, '[', ']');
    }

    /**
     * Attempt to find a wildcard view at the given directory with the given beginning and ending strings.
     */
    protected function findViewWith(string $directory, $startsWith, $endsWith): ?string
    {
        $files = (new Filesystem)->files($directory);

        return collect($files)->first(function ($file) use ($startsWith, $endsWith) {
            $filename = Str::of($file->getFilename());

            if (! $filename->endsWith(Folio::extension())) {
                return;
            }

            $filename = $filename->before(Folio::extension());

            return $filename->startsWith($startsWith) &&
                   $filename->endsWith($endsWith);
        })?->getFilename();
    }
}
