<?php

namespace Laravel\Folio;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;
use Laravel\Folio\Pipeline\ContinueIterating;
use Laravel\Folio\Pipeline\ContinueIteratingIfDirectoryWithFurtherSegments;
use Laravel\Folio\Pipeline\MatchDirectoryIndexViews;
use Laravel\Folio\Pipeline\MatchLiteralViews;
use Laravel\Folio\Pipeline\MatchRootIndex;
use Laravel\Folio\Pipeline\MatchWildcardDirectories;
use Laravel\Folio\Pipeline\MatchWildcardViews;
use Laravel\Folio\Pipeline\MatchWildcardViewsThatCaptureMultipleSegments;
use Laravel\Folio\Pipeline\State;
use Laravel\Folio\Pipeline\StopIterating;
use Laravel\Folio\Pipeline\StopIteratingIfDirectoryWithoutIndexOrFurtherSegments;

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
        $state = new State(
            uri: $uri,
            mountPath: $mountPath,
            segments: explode('/', $uri)
        );

        for ($i = 0; $i < $state->segmentCount(); $i++) {
            $value = (new Pipeline)
                        ->send($state->forIteration($i))
                        ->through([
                            new MatchRootIndex,
                            new MatchDirectoryIndexViews,
                            new StopIteratingIfDirectoryWithoutIndexOrFurtherSegments,
                            new ContinueIteratingIfDirectoryWithFurtherSegments,
                            new MatchLiteralViews,
                            new MatchWildcardViewsThatCaptureMultipleSegments,
                            new MatchWildcardViews,
                            new MatchWildcardDirectories,
                        ])->thenReturn(fn () => new StopIterating);

            if ($value instanceof ViewInstance) {
                return $value;
            } elseif ($value instanceof ContinueIterating) {
                $state = $value->state;

                continue;
            } elseif ($value instanceof StopIterating) {
                return null;
            }
        }

        return null;
    }
}
