<?php

declare(strict_types=1);

namespace WavyKit\Support;

use Closure;
use Throwable;
use WavyKit\Exceptions\ResolutionFailed;

/** @internal Resolves string-or-callback labels; shared by every reader. */
final class Label
{
    /**
     * @template TArgument
     *
     * @param  string|Closure(TArgument): mixed  $value
     * @param  TArgument  $argument  Passed to a callback: the request Context for pages and menus, the object for subjects.
     * @param  string  $owner  Names the page, menu entry, or subject in error messages.
     *
     * @throws ResolutionFailed If the callback throws or does not return a string.
     */
    public static function resolve(string|Closure $value, mixed $argument, string $owner): string
    {
        if (is_string($value)) {
            return $value;
        }
        try {
            $label = $value($argument);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not resolve label for {$owner}: {$exception->getMessage()}", 0, $exception);
        }
        if (! is_string($label)) {
            throw new ResolutionFailed("Label callback for {$owner} must return a string.");
        }

        return $label;
    }
}
