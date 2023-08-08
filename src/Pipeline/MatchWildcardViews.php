<?php

namespace Laravel\Folio\Pipeline;

use Closure;
use Illuminate\Support\Str;

class MatchWildcardViews
{
    use FindsWildcardViews;

    /**
     * Create a new pipeline step instance.
     */
    public function __construct(protected array $extensions) {

    }

    /**
     * Invoke the routing pipeline handler.
     */
    public function __invoke(State $state, Closure $next): mixed
    {
        if ($state->onLastUriSegment() &&
            $path = $this->findWildcardView($state->currentDirectory(), $this->extensions)) {
            $str = Str::of($path);

            foreach ($this->extensions as $extension) {
                $str->before($extension);
            }

            return new MatchedView($state->currentDirectory().'/'.$path, $state->withData(
                $str->match('/\[(.*)\]/')->value(),
                $state->currentUriSegment(),
            )->data);
        }

        return $next($state);
    }
}
