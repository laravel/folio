<?php

namespace Laravel\Folio;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PotentiallyBindablePathSegment
{
    protected $class;

    public function __construct(public string $value)
    {
    }

    /**
     * Determine if the segment is bindable.
     */
    public function bindable(): bool
    {
        return str_starts_with($this->value, '[') &&
               str_ends_with($this->value, ']') &&
               class_exists($this->class()) &&
               is_a($this->class(), UrlRoutable::class, true);
    }

    /**
     * Determine if the binding segment captures multiple segments.
     */
    public function capturesMultipleSegments(): bool
    {
        return str_starts_with($this->value, '[...');
    }

    /**
     * Resolve the binding or throw a ModelNotFoundException.
     */
    public function resolveOrFail(mixed $value): UrlRoutable
    {
        if (is_null($resolved = $this->resolve($value))) {
            throw (new ModelNotFoundException)
                    ->setModel(get_class($this->newClassInstance()), [$value]);
        }

        return $resolved;
    }

    /**
     * Attempt to resolve the binding.
     */
    public function resolve(mixed $value): ?UrlRoutable
    {
        $classInstance = $this->newClassInstance();

        if ($explicitBindingCallback = Route::getBindingCallback($this->variable())) {
            return $explicitBindingCallback($value);
        }

        return $classInstance->resolveRouteBinding(
            $value, $this->field() ?: $classInstance->getRouteKeyName()
        );
    }

    /**
     * Get the class name contained by the bindable segment.
     */
    public function class(): string
    {
        if ($this->class) {
            return $this->class;
        }

        $this->class = (string) Str::of($this->value)
                    ->trim('[]')
                    ->after('...')
                    ->before('-')
                    ->before('|')
                    ->before(':')
                    ->replace('.', '\\')
                    ->unless(
                        fn ($s) => $s->contains('\\'),
                        fn ($s) => $s->prepend('App\\Models\\')
                    )->trim('\\');

        return $this->class;
    }

    /**
     * Get the basename of the class being bound.
     */
    public function classBasename(): string
    {
        return class_basename($this->class());
    }

    /**
     * Get a new class instance for the binding class.
     */
    public function newClassInstance(): mixed
    {
        return Container::getInstance()->make($this->class());
    }

    /**
     * Get the custom binding field (if any) that is specified in the segment.
     */
    public function field(): string|bool
    {
        if (str_contains($this->value, ':')) {
            return Str::of($this->trimmed())->after(':')->before('|')->before('$')->value();
        } elseif (str_contains($this->value, '-')) {
            return explode('-', $this->trimmed())[1] ?? false;
        }

        return false;
    }

    /**
     * Get the view injectable variable name for the class being bound.
     */
    public function variable(): string
    {
        if (str_contains($this->value, '|')) {
            return Str::of($this->trimmed())->afterLast('|')->value();
        } elseif (str_contains($this->value, '$')) {
            return Str::of($this->trimmed())->afterLast('$')->value();
        }

        return $this->capturesMultipleSegments()
                    ? Str::camel(Str::plural($this->classBasename()))
                    : Str::camel($this->classBasename());
    }

    /**
     * Get the segment value with the "[" and "]" characters trimmed.
     */
    public function trimmed(): string
    {
        return trim($this->value, '[]');
    }
}
