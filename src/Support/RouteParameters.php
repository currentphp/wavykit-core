<?php

declare(strict_types=1);

namespace WavyKit\Support;

use Closure;
use Throwable;
use WavyKit\Exceptions\ResolutionFailed;

/** @internal Resolves route-parameter callbacks; shared by pages and subjects. */
final class RouteParameters
{
    /**
     * @template TArgument
     *
     * @param  Closure(TArgument): mixed  $callback
     * @param  TArgument  $argument  The request Context for pages, the object for subjects.
     * @param  string  $owner  Names the page or subject in error messages.
     * @return array<string, mixed>
     *
     * @throws ResolutionFailed If the callback throws or does not return an array keyed by parameter name.
     */
    public static function resolve(Closure $callback, mixed $argument, string $owner): array
    {
        try {
            $mapped = $callback($argument);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not resolve route parameters for {$owner}: {$exception->getMessage()}", 0, $exception);
        }
        if (! is_array($mapped)) {
            throw new ResolutionFailed("Route-parameter callback for {$owner} must return an array keyed by route parameter name.");
        }
        $parameters = [];
        foreach ($mapped as $name => $value) {
            if (! is_string($name)) {
                throw new ResolutionFailed("Route-parameter callback for {$owner} must return an array keyed by route parameter name.");
            }
            $parameters[$name] = $value;
        }

        return $parameters;
    }
}
