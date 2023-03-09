<?php

use Laravel\Folio\PotentiallyBindablePathSegment;

test('test model directory is assumed for classes that are not fully qualified and do not exist', function () {
    $segment = new PotentiallyBindablePathSegment('[User]');

    $this->assertEquals('App\\Models\\User', $segment->class());
});
