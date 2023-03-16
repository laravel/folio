<?php

namespace Laravel\Folio\Pipeline;

use BackedEnum;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
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
        if (! str_starts_with($this->value, '[') ||
            ! str_ends_with($this->value, ']') ||
            ! class_exists($this->class())) {
            return false;
        }

        if (enum_exists($this->class())) {
            return true;
        }

        if (! is_a($this->class(), UrlRoutable::class, true)) {
            throw new Exception('Folio route attempting to bind to class ['.$this->class().'], but it does not implement the UrlRoutable interface.');
        }

        return true;
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
    public function resolveOrFail(mixed $value, ?PotentiallyBindablePathSegment $parent = null): UrlRoutable|BackedEnum
    {
        if (is_null($resolved = $this->resolve($value, $parent))) {
            throw (new ModelNotFoundException)
                    ->setModel(get_class($this->newClassInstance()), [$value]);
        }

        return $resolved;
    }

    /**
     * Attempt to resolve the binding.
     */
    public function resolve(mixed $value, ?PotentiallyBindablePathSegment $parent = null): mixed
    {
        if ($explicitBindingCallback = Route::getBindingCallback($this->variable())) {
            return $explicitBindingCallback($value);
        }

        if (enum_exists($this->class())) {
            return $this->resolveEnum($value);
        } elseif ($parent && $this->field()) {
            return $this->resolveViaParent($value, $parent);
        }

        $classInstance = $this->newClassInstance();

        return $classInstance->resolveRouteBinding(
            $value, $this->field() ?: $classInstance->getRouteKeyName()
        );
    }

    /**
     * Resolve the binding as an Enum.
     */
    protected function resolveEnum(mixed $value): BackedEnum
    {
        $backedEnumClass = $this->class();

        if (is_null($backedEnum = $backedEnumClass::tryFrom((string) $value))) {
            throw new BackedEnumCaseNotFoundException($backedEnumClass, $value);
        }

        return $backedEnum;
    }

    /**
     * Attempt to resolve the binding via the given parent.
     */
    protected function resolveViaParent(mixed $value, PotentiallyBindablePathSegment $parent): ?UrlRoutable
    {
        [$parentInstance, $childInstance] = [
            $parent->newClassInstance(),
            $this->newClassInstance(),
        ];

        return $parentInstance->resolveChildRouteBinding(
            get_class($this->newClassInstance()),
            $value,
            $this->field() ?: $childInstance->getRouteKeyName()
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
                        fn ($s) => $s->contains('\\') || class_exists($s->value()),
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
            return with(explode('-', $this->trimmed()), fn (array $segments) =>
                str_contains($segments[1] ?? '', '$') ? false : $segments[1]
            );
        }

        return false;
    }

    /**
     * Get the view injectable variable name for the class being bound.
     */
    public function variable(): string
    {
        if (str_contains($this->value, '|')) {
            return Str::of($this->trimmed())->afterLast('|')->trim('$')->value();
        } elseif (str_contains($this->value, '$')) {
            return Str::of($this->trimmed())->afterLast('$')->value();
        }

        return $this->capturesMultipleSegments()
                    ? Str::camel(Str::plural($this->classBasename()))
                    : Str::camel($this->classBasename());
    }

    /**
     * Get the segment value with the "[" and "]" and "..." characters trimmed.
     */
    public function trimmed(): string
    {
        return Str::of($this->value)->trim('[]')->after('...')->value();
    }
}
