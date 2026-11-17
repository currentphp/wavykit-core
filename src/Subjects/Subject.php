<?php

declare(strict_types=1);

namespace WavyKit\Subjects;

use Closure;

/**
 * A declaration builder for a kind of subject, such as a project or a task.
 *
 * Registration takes a snapshot. Callbacks receive the subject object, typed by
 * the application, and must be free of side effects; WavyKit never loads or
 * queries objects, only describes ones the application has bound.
 */
final class Subject
{
    /** @var class-string|null */
    private ?string $class = null;

    private ?Closure $label = null;

    private ?string $pageId = null;

    private ?Closure $routeParameters = null;

    private function __construct(public readonly string $name) {}

    /** Start a declaration with a unique, non-empty kind name, such as `project`. */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * The class whose instances are subjects of this kind; it must be routable
     * (UrlRoutable, such as an Eloquent model) and must not overlap another kind's class.
     *
     * @param  class-string  $class
     */
    public function for(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Label a subject from its object, for example `fn (Project $project) => $project->name`.
     *
     * Required. Pages that present this kind without their own label use it.
     */
    public function label(callable $label): self
    {
        $this->label = Closure::fromCallable($label);

        return $this;
    }

    /**
     * Name the canonical page that shows a subject of this kind; that page must present it.
     *
     * By default the route parameter named after the kind receives the object.
     * Otherwise return an array keyed by route parameter name from the object,
     * for example `fn (Task $task) => ['project' => $task->project, 'task' => $task]`.
     * Without a page, a subject has no URL.
     */
    public function page(string $pageId, ?callable $routeParameters = null): self
    {
        $this->pageId = $pageId;
        $this->routeParameters = $routeParameters === null ? null : Closure::fromCallable($routeParameters);

        return $this;
    }

    /** @internal Snapshot taken by SubjectRegistry. */
    public function toDefinition(): SubjectDefinition
    {
        return new SubjectDefinition($this->name, $this->class, $this->label, $this->pageId, $this->routeParameters);
    }
}
