<?php

namespace Laravel\Folio;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pipeline\Pipeline;
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
    public function __construct(protected array $mountPaths)
    {
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
                static::ensureNoDirectoryTraversal($value->path, $state->mountPath);

                return static::transformModelBindings($value, $state);
            } elseif ($value instanceof ContinueIterating) {
                $state = $value->state;

                continue;
            } elseif ($value instanceof StopIterating) {
                return null;
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

        [$uriSegments, $pathSegments] = [
            explode('/', $state->uri),
            explode('/', $path),
        ];

        foreach ($pathSegments as $index => $segment) {
            if (! str_starts_with($segment, '[') ||
                ! str_ends_with($segment, ']')) {
                continue;
            }

            // TODO: Explicit bindings...
            // TODO: Multi-segments...

            $class = (string) Str::of($segment)
                        ->trim('[]')
                        ->beforeLast('-')
                        ->replace('.', '\\');

            if (! str_contains($class, '\\')) {
                $class = 'App\\Models\\'.$class;
            }

            if (! class_exists($class)) {
                continue;
            }

            $classInstance = Container::getInstance()->make($class);

            if (! $classInstance instanceof UrlRoutable) {
                continue;
            }

            $resolved = $classInstance->resolveRouteBinding(
                $uriSegments[$index], $classInstance->getRouteKeyName()
            );

            if (! $resolved) {
                throw (new ModelNotFoundException)->setModel(get_class($classInstance), [$uriSegments[$index]]);
            }

            unset($view->data[Str::of($segment)->trim('[]')->value()]);

            $view->data[Str::camel(class_basename($class))] = $resolved;
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
