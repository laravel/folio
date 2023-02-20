<?php

namespace Laravel\Folio;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewInstance;

class Router
{
    public function __construct(protected array $mountPaths)
    {
    }

    /**
     * Resolve the given URI via page based routing.
     */
    public function resolve(string $uri): ?ViewInstance
    {
        foreach ($this->mountPaths as $mountPath) {
            if ($view = $this->resolveAtPath($mountPath, $uri)) {
                return $view;
            }
        }

        return null;
    }

    /**
     * Resolve the given URI via page based routing at the given mount path.
     */
    protected function resolveAtPath(string $mountPath, string $uri): ?ViewInstance
    {
        if (trim($uri) === '/') {
            return file_exists($indexView = $mountPath.'/index.blade.php')
                    ? View::file($indexView, $data)
                    : null;
        }

        $state = new Pipeline\State(
            mountPath: $mountPath,
            segments: explode('/', $uri)
        );

        for ($i = 0; $i < $state->segmentCount(); $i++) {
            $state = $state->forIteration($i);

            // Literal directory match...
            if (is_dir($state->absoluteDirectory().'/'.$state->currentSegment())) {
                // Index view match (must also be last segment)...
                if ($state->lastSegment() &&
                    file_exists($indexView = $state->absoluteDirectory().'/'.$state->currentSegment().'/index.blade.php')) {
                    // Possible directory traversal...
                    Functions::ensureNoDirectoryTraversal($indexView, $state->mountPath);

                    return View::file($indexView, $state->data);
                }

                // Is a directory, but no index view and no further segments available...
                if ($state->lastSegment()) {
                    return null;
                }

                // Is a directory and further segments available, continue...
                continue;
            }

            // Literal view match... must also be last segment...
            if ($state->lastSegment() && file_exists($possibleView = $state->absoluteDirectory().'/'.$state->currentSegment().'.blade.php')) {
                Functions::ensureNoDirectoryTraversal($possibleView, $state->mountPath);

                return View::file($possibleView, $state->data);
            }

            // Wildcard, multi-segment view match...
            $possibleView = Functions::findWildcardMultiSegmentView($state->absoluteDirectory());

            if ($possibleView) {
                $state = $state->withData(
                    Str::of($possibleView)
                        ->before('.blade.php')
                        ->match('/\[\.\.\.(.*)\]/')->value(),
                    array_slice($state->segments, $i, $state->segmentCount() - 1)
                );

                Functions::ensureNoDirectoryTraversal($state->absoluteDirectory().'/'.$possibleView, $state->mountPath);

                return View::file($state->absoluteDirectory().'/'.$possibleView, $state->data);
            }

            // Wildcard view match... must also be last segment...
            if ($state->lastSegment() &&
                $possibleView = Functions::findWildcardView($state->absoluteDirectory())) {
                Functions::ensureNoDirectoryTraversal($state->absoluteDirectory().'/'.$possibleView, $state->mountPath);

                $state = $state->withData(
                    Str::of($possibleView)
                        ->before('.blade.php')
                        ->match('/\[(.*)\]/')->value(),
                    $state->currentSegment(),
                );

                return View::file($state->absoluteDirectory().'/'.$possibleView, $state->data);
            }

            // Wildcard directory match... (there are further segments and a wildcard directory is present?)
            if (! $state->lastSegment() &&
                $possibleDirectory = Functions::findWildcardDirectory($state->absoluteDirectory())) {
                $state = $state->withData(
                    Str::of($possibleDirectory)
                        ->basename()
                        ->match('/\[(.*)\]/')->value(),
                    $state->currentSegment(),
                );

                $state->segments[$i] = Str::of($possibleDirectory)->basename();

                continue;
            }

            // Otherwise, 404?
            return null;
        }

        return null;
    }
}
