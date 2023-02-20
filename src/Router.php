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

        $segments = explode('/', $uri);
        $segmentCount = count($segments);
        $data = [];

        for ($i = 0; $i < $segmentCount; $i++) {
            $currentSegment = $segments[$i];
            $lastSegment = $i === ($segmentCount - 1);
            $relativeDirectory = implode('/', array_slice($segments, 0, $i));
            $absoluteDirectory = $mountPath.'/'.$relativeDirectory;

            // Literal directory match...
            if (is_dir($absoluteDirectory.'/'.$currentSegment)) {
                // Index view match (must also be last segment)...
                if ($lastSegment &&
                    file_exists($indexView = $absoluteDirectory.'/'.$currentSegment.'/index.blade.php')) {
                    // Possible directory traversal...
                    Functions::ensureNoDirectoryTraversal($indexView, $mountPath);

                    return View::file($indexView, $data);
                }

                // Is a directory, but no index view and no further segments available...
                if ($lastSegment) {
                    return null;
                }

                // Is a directory and further segments available, continue...
                continue;
            }

            // Literal view match... must also be last segment...
            if ($lastSegment && file_exists($possibleView = $absoluteDirectory.'/'.$currentSegment.'.blade.php')) {
                Functions::ensureNoDirectoryTraversal($possibleView, $mountPath);

                return View::file($possibleView, $data);
            }

            // Wildcard, multi-segment view match...
            $possibleView = Functions::findWildcardMultiSegmentView($absoluteDirectory);

            if ($possibleView) {
                $data[
                    Str::of($possibleView)
                        ->before('.blade.php')
                        ->match('/\[\.\.\.(.*)\]/')->value()
                ] = array_slice($segments, $i, $segmentCount - 1);

                Functions::ensureNoDirectoryTraversal($absoluteDirectory.'/'.$possibleView, $mountPath);

                return View::file($absoluteDirectory.'/'.$possibleView, $data);
            }

            // Wildcard view match... must also be last segment...
            if ($lastSegment &&
                $possibleView = Functions::findWildcardView($absoluteDirectory)) {
                Functions::ensureNoDirectoryTraversal($absoluteDirectory.'/'.$possibleView, $mountPath);

                $data[
                    Str::of($possibleView)
                        ->before('.blade.php')
                        ->match('/\[(.*)\]/')->value()
                ] = $currentSegment;

                return View::file($absoluteDirectory.'/'.$possibleView, $data);
            }

            // Wildcard directory match... (there are further segments and a wildcard directory is present?)
            if (! $lastSegment &&
                $possibleDirectory = Functions::findWildcardDirectory($absoluteDirectory)) {
                $data[
                    Str::of($possibleDirectory)
                        ->basename()
                        ->match('/\[(.*)\]/')->value()
                ] = $currentSegment;

                $segments[$i] = Str::of($possibleDirectory)->basename();

                continue;
            }

            // Otherwise, 404?
            return null;
        }

        return null;
    }
}
