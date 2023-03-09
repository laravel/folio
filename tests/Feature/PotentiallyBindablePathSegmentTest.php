<?php

use Laravel\Folio\PotentiallyBindablePathSegment;

test('test model directory is assumed for classes that are not fully qualified and do not exist', function () {
    $segment = new PotentiallyBindablePathSegment('[User]');

    $this->assertEquals('App\\Models\\User', $segment->class());
});

test('field parsing', function (string $segment, string $field) {
    $segment = new PotentiallyBindablePathSegment($segment);

    $this->assertEquals($field, $segment->field());
})->with([
    ['[User]', false],
    ['[User:slug]', 'slug'],
    ['[User-slug]', 'slug'],
    ['[User|theUser]', false],
    ['[User-$theUser]', false],
    ['[User:slug|theUser]', 'slug'],
    ['[User-slug-$theUser]', 'slug'],
]);

test('variable parsing', function (string $segment, string $variable) {
    $segment = new PotentiallyBindablePathSegment($segment);

    $this->assertEquals($variable, $segment->variable());
})->with([
    ['[User|theUser]', 'theUser'],
    ['[User:slug|theUser]', 'theUser'],
    ['[User-$theUser]', 'theUser'],
    ['[User-slug-$theUser]', 'theUser'],
]);
