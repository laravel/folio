<?php
use function Laravel\Folio\{page};

page(middleware: [function ($request, $next) {
    $_SERVER['__folio_flights_inline_middleware'] = true;
    return $next($request);
}]);
?>

<div>
    Flight Index
</div>
