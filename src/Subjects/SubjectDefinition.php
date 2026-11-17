<?php

declare(strict_types=1);

namespace WavyKit\Subjects;

use Closure;

/** Immutable registration snapshot of a subject kind; callbacks stay unevaluated until output is requested. */
final readonly class SubjectDefinition
{
    /**
     * @internal Created by SubjectRegistry from a Subject builder.
     *
     * @param  class-string|null  $class  Null only before validation fails.
     */
    public function __construct(
        public string $name,
        public ?string $class,
        public ?Closure $label,
        public ?string $pageId,
        public ?Closure $routeParameters = null,
    ) {}
}
