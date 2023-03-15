<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class RequestHandler
{
    public function __construct(protected string $mountPath,
                                protected array $pathMiddleware = [],
                                protected ?Closure $renderUsing = null)
    {
    }

    public function __invoke(Request $request, string $uri)
    {
        $middleware = (new PathBasedMiddlewareList($this->pathMiddleware))->match(
            $matchedView = (new Router(Arr::wrap($this->mountPath)))->resolve($uri) ?? abort(404)
        );

        $middleware = $middleware->prepend('web')->unique();

        // Gather middleware from the matched file...

        return (new Pipeline(app()))
            ->send($request)
            ->through(Route::resolveMiddleware($middleware->all()))
            ->then(function ($request) use ($matchedView) {
                if ($this->renderUsing) {
                    return call_user_func($this->renderUsing, $request, $matchedView);
                }

                return new Response(
                    View::file($matchedView->path, $matchedView->data),
                    200,
                    [
                        'Content-Type' => 'text/html',
                        'X-Folio' => 'True',
                    ]
                );
            });
    }
}
