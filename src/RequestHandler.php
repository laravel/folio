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
    public function __construct(protected MountPath $mountPath,
                                protected ?Closure $renderUsing = null)
    {
    }

    public function __invoke(Request $request, string $uri)
    {
        $matchedView = (new Router(
            $this->mountPath->path
        ))->resolve($uri) ?? abort(404);

        $middleware = $this->mountPath
                ->middleware
                ->match($matchedView)
                ->prepend('web')
                ->merge(
                    $matchedView->inlineMiddleware()
                )->unique()
                ->values();

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
