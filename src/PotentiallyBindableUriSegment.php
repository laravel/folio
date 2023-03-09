<?php

namespace Laravel\Folio;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Str;

class PotentiallyBindableUriSegment
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
     * Attempt to resolve the binding.
     */
    public function resolve(mixed $value): ?UrlRoutable
    {
        $classInstance = $this->newClassInstance();

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
                    ->beforeLast('-')
                    ->beforeLast('|')
                    ->replace('.', '\\')
                    ->unless(
                        fn ($s) => $s->contains('\\'),
                        fn ($s) => $s->prepend('App\\Models\\')
                    );

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
     * Get the view injectable variable name for the class being bound.
     */
    public function classVariable(): string
    {
        return Str::camel($this->classBasename());
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
        if (str_contains($this->value, '|')) {
            return Str::of($this->trimmed())->afterLast('|')->value();
        } elseif (str_contains($this->value, '-')) {
            return Str::of($this->trimmed())->afterLast('-')->value();
        }

        return false;
    }

    /**
     * Get the segment value with the "[" and "]" characters trimmed.
     */
    public function trimmed(): string
    {
        return trim($this->value, '[]');
    }
}
