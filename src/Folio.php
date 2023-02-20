<?php

namespace Laravel\Folio;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class Folio
{
    /**
     * Mount the given paths as page based routing targets.
     */
    public static function mount(string $mountPath): void
    {
        Route::get('/{uri?}', function ($uri = '/') use ($mountPath) {
            if (trim($uri) === '/') {
                return file_exists($indexView = $mountPath.'/index.blade.php')
                        ? View::file($indexView, $data)
                        : abort(404);
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
                        abort(404);
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
                abort(404);
            }

            abort(404);
        })->where('uri', '.*');
    }
}
