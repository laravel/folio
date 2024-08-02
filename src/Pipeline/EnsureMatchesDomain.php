<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Route;
use Laravel\Folio\MountPath;

class EnsureMatchesDomain
{
    /**
     * Create a new pipeline step instance.
     */
    public function __construct(protected Request $request, protected MountPath $mountPath) {}

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($this->mountPath->domain === null) {
            return $next($state);
        }

        $route = $this->route();

        if ($this->matchesDomain($route) === false) {
            return new StopIterating;
        }

        $state->data = array_merge($route->parameters(), $state->data);

        return $next($state);
    }

    /**
     * Get the route that should be used to match the request.
     */
    protected function route(): Route
    {
        return (new Route(['GET'], $this->mountPath->baseUri, fn () => null))
            ->domain($this->mountPath->domain)
            ->bind($this->request);
    }

    /**
     * Determine if the request matches the domain.
     */
    protected function matchesDomain(Route $route): bool
    {
        return (bool) (new HostValidator)->matches($route, $this->request);
    }
}
