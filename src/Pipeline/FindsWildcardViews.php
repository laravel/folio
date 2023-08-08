<?php

namespace Laravel\Folio\Pipeline;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait FindsWildcardViews
{
    /**
     * Attempt to find a wildcard multi-segment view at the given directory.
     */
    protected function findWildcardMultiSegmentView(string $directory, array $extensions = []): ?string
    {
        return $this->findViewWith($directory, '[...', ']', $extensions);
    }

    /**
     * Attempt to find a wildcard view at the given directory.
     */
    protected function findWildcardView(string $directory, array $extensions = []): ?string
    {
        return $this->findViewWith($directory, '[', ']', $extensions);
    }

    /**
     * Attempt to find a wildcard view at the given directory with the given beginning and ending strings.
     */
    protected function findViewWith(string $directory, $startsWith, $endsWith, array $extensions = []): ?string
    {
        $files = (new Filesystem)->files($directory);

        return collect($files)->first(function ($file) use ($startsWith, $endsWith, $extensions) {
            $filename = Str::of($file->getFilename());

            $extensionFound = false;
            foreach ($extensions as $extension) {
                if ($filename->endsWith($extension)) {
                    $extensionFound = true;
                    $filename = $filename->before($extension);
                    break;
                }
            }

            if (! $extensionFound) {
                return;
            }

            return $filename->startsWith($startsWith) &&
                   $filename->endsWith($endsWith);
        })?->getFilename();
    }
}
