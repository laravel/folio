<?php

namespace Laravel\Folio\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Laravel\Folio\Pipeline\MatchedView;
use Symfony\Component\HttpFoundation\Response;

class InertiaDriver implements FolioDriverContract
{
    public function extension(): string
    {
        return '.tsx';
    }

    public function requirePath($path): string
    {
        return str($path)->replaceEnd($this->extension(), ".php")->toString();
    }

    /**
     * Create a response instance for the given matched view.
     */
    public function toResponse(Request $request, MatchedView $matchedView): Response
    {
        $inertiaPath = str($matchedView->path)->replaceStart($matchedView->mountPath, '')->replaceStart('/', '')->replaceEnd($this->extension(), '')->__toString();

        $view = Inertia::render($inertiaPath, $matchedView->data);

        return Route::toResponse($request, app()->call(
            $matchedView->renderUsing(),
            ['view' => $view, ...((fn () => $this->props)->call($view))]
        ) ?? $view);
    }
}
