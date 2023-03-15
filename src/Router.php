<?php

namespace Laravel\Folio;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Folio\Exceptions\PossibleDirectoryTraversal;
use Laravel\Folio\Pipeline\ContinueIterating;
use Laravel\Folio\Pipeline\MatchDirectoryIndexViews;
use Laravel\Folio\Pipeline\MatchedView;
use Laravel\Folio\Pipeline\MatchLiteralDirectories;
use Laravel\Folio\Pipeline\MatchLiteralViews;
use Laravel\Folio\Pipeline\MatchRootIndex;
use Laravel\Folio\Pipeline\MatchWildcardDirectories;
use Laravel\Folio\Pipeline\MatchWildcardViews;
use Laravel\Folio\Pipeline\MatchWildcardViewsThatCaptureMultipleSegments;
use Laravel\Folio\Pipeline\State;
use Laravel\Folio\Pipeline\StopIterating;

class Router
{
    /**
     * The array of mount paths that contain routable pages.
     */
    protected array $mountPaths;

    /**
     * Create a new router instance.
     */
    public function __construct(array|string $mountPaths)
    {
        $this->mountPaths = Arr::wrap($mountPaths);
    }

    /**
     * Resolve the given URI via page based routing.
     */
    public function resolve(string $uri): ?MatchedView
    {
        $uri = strlen($uri) > 1 ? trim($uri, '/') : $uri;

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
    protected function resolveAtPath(string $mountPath, string $uri): ?MatchedView
    {
        $state = new State(
            uri: $uri,
            mountPath: $mountPath,
            segments: explode('/', $uri)
        );

        for ($i = 0; $i < $state->uriSegmentCount(); $i++) {
            $value = (new Pipeline)
                        ->send($state->forIteration($i))
                        ->through([
                            new MatchRootIndex,
                            new MatchDirectoryIndexViews,
                            new MatchWildcardViewsThatCaptureMultipleSegments,
                            new MatchLiteralDirectories,
                            new MatchWildcardDirectories,
                            new MatchLiteralViews,
                            new MatchWildcardViews,
                        ])->then(fn () => new StopIterating);

            if ($value instanceof MatchedView) {
                $value = $value->withMountPath($mountPath);

                static::ensureNoDirectoryTraversal($value->path, $state->mountPath);

                return static::transformModelBindings($value, $state);
            } elseif ($value instanceof ContinueIterating) {
                $state = $value->state;

                continue;
            } elseif ($value instanceof StopIterating) {
                break;
            }
        }

        return null;
    }

    /**
     * Transform the model bindings for the matched view.
     */
    protected static function transformModelBindings(MatchedView $view, State $state): MatchedView
    {
        $path = (string) Str::of($view->path)
            ->replace($state->mountPath, '')
            ->beforeLast('.blade.php')
            ->trim('/');

        [$parent, $uriSegments, $pathSegments] = [
            null, explode('/', $state->uri), explode('/', $path),
        ];

        foreach ($pathSegments as $index => $segment) {
            $segment = new PotentiallyBindablePathSegment($segment);

            if (! $segment->bindable()) {
                continue;
            }

            if ($segment->capturesMultipleSegments()) {
                return $view->replace(
                    $segment->trimmed(),
                    $segment->variable(),
                    collect(array_slice($uriSegments, $index))
                        ->map(fn ($value) => $segment->resolveOrFail($value, $parent))
                        ->all(),
                );
            }

            // TODO: withTrashed support...

            $parent = $segment;

            $view = $view->replace(
                $segment->trimmed(),
                $segment->variable(),
                $segment->resolveOrFail($uriSegments[$index], $parent),
            );
        }

        return $view;
    }

    /**
     * Ensure that a possible directory traversal is not happening.
     */
    public static function ensureNoDirectoryTraversal(string $path, string $mountPath): void
    {
        if (! Str::of(realpath($path))->startsWith($mountPath.'/')) {
            throw new PossibleDirectoryTraversal;
        }
    }
}
