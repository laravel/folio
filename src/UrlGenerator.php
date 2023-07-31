<?php

namespace Laravel\Folio;

use BackedEnum;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Str;
use Laravel\Folio\Exceptions\UrlGenerationException;
use Laravel\Folio\Pipeline\PotentiallyBindablePathSegment;

class UrlGenerator
{
    /**
     * Generate the URL to a Folio page.
     *
     * @throws \Laravel\Folio\Exceptions\UrlGenerationException
     */
    public function path(MountPath $mountPath, string $path, array $parameters = []): string
    {
        $uri = str_replace('.blade.php', '', $path);

        $uri = collect(explode('/', $uri))
            ->map(function (string $segment) use ($parameters, $uri) {
                if (! Str::startsWith($segment, '[')) {
                    return $segment;
                }

                $segment = new PotentiallyBindablePathSegment($segment);
                $name = $segment->variable();

                if (! isset($parameters[$name])) {
                    throw UrlGenerationException::forMissingParameter($uri, $name);
                }

                return $this->formatParameter($parameters[$name], $segment->field(), $segment->capturesMultipleSegments());
            })
            ->implode('/');

        $uri = str_replace(['/index', '/index/'], ['', '/'], $uri);

        return ltrim($mountPath->baseUri, '/') . '/' . $uri;
    }

    /**
     * Formats the given URL parameter.
     */
    protected function formatParameter(mixed $parameter, string|bool $field, bool $variadic): mixed
    {
        if ($parameter instanceof UrlRoutable && $field !== false) {
            return $parameter->{$field};
        }

        if ($parameter instanceof UrlRoutable) {
            return $parameter->getRouteKey();
        }

        if ($parameter instanceof BackedEnum) {
            return $parameter->value;
        }

        if ($variadic) {
            return implode(
                '/',
                collect($parameter)
                    ->map(fn (mixed $value) => $this->formatParameter($value, $field, false))
                    ->all()
            );
        }

        return $parameter;
    }
}
