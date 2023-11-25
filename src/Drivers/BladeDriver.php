<?php

namespace Laravel\Folio\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Pipeline\MatchedView;
use Symfony\Component\HttpFoundation\Response;

class BladeDriver
{
    public function extension(): string
    {
        return '.blade.php';
    }

    public function requirePath($path): string
    {
        return $path;
    }

    /**
     * Create a response instance for the given matched view.
     */
    public function toResponse(Request $request, MatchedView $matchedView): Response
    {
        $view = View::file($matchedView->path, $matchedView->data);

        return Route::toResponse($request, app()->call(
            $matchedView->renderUsing(),
            ['view' => $view, ...$view->getData()]
        ) ?? $view);
    }
}
