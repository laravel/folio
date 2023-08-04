<?php

namespace Laravel\Folio;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Pipeline\MatchedView;

class RequestHandler
{
    /**
     * Create a new request handler instance.
     */
    public function __construct(
        protected FolioManager $manager,
        protected MountPath $mountPath,
        protected ?Closure $renderUsing = null,
        protected ?Closure $onViewMatch = null,
    ) {
    }

    /**
     * Handle the incoming request using Folio.
     */
    public function __invoke(Request $request, string $uri): mixed
    {
        $matchedView = (new Router(
            $this->mountPath
        ))->match($request, $uri) ?? abort(404);

        app(Dispatcher::class)->dispatch(new Events\ViewMatched($matchedView, $this->mountPath));

        $middleware = collect($this->middleware($matchedView));

        return (new Pipeline(app()))
            ->send($request)
            ->through($middleware->all())
            ->then(function (Request $request) use ($matchedView, $middleware) {
                if ($this->onViewMatch) {
                    ($this->onViewMatch)($matchedView);
                }

                $response = $this->renderUsing
                    ? ($this->renderUsing)($request, $matchedView)
                    : $this->toResponse($matchedView);

                $this->manager->terminateUsing(
                    fn (Application $app) => $middleware->filter(fn ($middleware) => is_string($middleware) && class_exists($middleware) && method_exists($middleware, 'terminate'))
                        ->map(fn (string $middleware) => $app->make($middleware))
                        ->each(fn (object $middleware) => $app->call([$middleware, 'terminate'], ['request' => $request, 'response' => $response]))
                );

                return $response;
            });
    }

    /**
     * Get the middleware that should be applied to the matched view.
     */
    protected function middleware(MatchedView $matchedView): array
    {
        return Route::resolveMiddleware(
            $this->mountPath
                ->middleware
                ->match($matchedView)
                ->prepend('web')
                ->merge($matchedView->inlineMiddleware())
                ->unique()
                ->values()
                ->all()
        );
    }

    /**
     * Create a response instance for the given matched view.
     */
    protected function toResponse(MatchedView $matchedView): Response
    {
        return new Response(
            View::file($matchedView->path, $matchedView->data),
            200,
            ['Content-Type' => 'text/html'],
        );
    }
}
