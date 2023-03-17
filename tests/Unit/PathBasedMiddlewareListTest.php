<?php

use Laravel\Folio\PathBasedMiddlewareList;
use Laravel\Folio\Pipeline\MatchedView;

test('path based middleware can be matched', function () {
    $list = new PathBasedMiddlewareList([
        '*' => ['foo'],
        '/index.blade.php' => ['bar'],
        '/index*' => 'baz',
    ]);

    $middleware = $list->match(
        new MatchedView('/test/path/index.blade.php', [], '/test/path')
    );

    $this->assertEquals(['foo', 'bar', 'baz'], $middleware->all());
});

test('path based middleware with wildcards can be matched', function () {
    $list = new PathBasedMiddlewareList([
        '/users/*' => ['foo'],
        '/users/[id].blade.php' => ['bar'],
    ]);

    $middleware = $list->match(
        new MatchedView('/test/path/users/[id].blade.php', [], '/test/path')
    );

    $this->assertEquals(['foo', 'bar'], $middleware->all());
});
