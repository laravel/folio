<?php

namespace Laravel\Folio\Drivers;

use Illuminate\Http\Request;
use Laravel\Folio\Pipeline\MatchedView;
use Symfony\Component\HttpFoundation\Response;

interface FolioDriverContract
{
    public function extension(): string;
    public function toResponse(Request $request, MatchedView $matchedView): Response;
    public function requirePath($path): string;
}
