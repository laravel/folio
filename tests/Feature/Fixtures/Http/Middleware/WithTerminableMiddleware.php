<?php

namespace Tests\Feature\Fixtures\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Fixtures\Dependency;

class WithTerminableMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $_SERVER['__folio_*_middleware'] = true;

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response, Dependency $dependency): void
    {
        $_SERVER['__folio_*_middleware.terminate']++;
        $_SERVER['__folio_*_middleware.terminate.dependency'] = $dependency;

        if ($_SERVER['__folio_*_middleware.terminate.should_fail']) {
            throw new Exception('Terminate failed.');
        }
    }
}
