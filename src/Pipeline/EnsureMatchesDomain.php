<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Route;
use Laravel\Folio\MountPath;

class EnsureMatchesDomain
{
    /**
     * Create a new pipeline step instance.
     */
    public function __construct(protected Request $request, protected MountPath $mountPath)
    {
    }

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($this->mountPath->domain === null) {
            return $next($state);
        }

        $route = (new Route(['GET'], $this->mountPath->baseUri, fn () => null))
            ->domain($this->mountPath->domain)
            ->bind($this->request);

        if (! (new HostValidator)->matches($route, $this->request)) {
            return new Response(status: 404);
        }

        $state->data = array_merge($state->data, $route->parameters());

        return $next($state);
    }
}
