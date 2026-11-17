<?php

declare(strict_types=1);

namespace WavyKit\Subjects;

use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Exceptions\ResolutionFailed;
use WavyKit\Pages\PageDefinition;
use WavyKit\Pages\PageRegistry;

/** Subject kinds shared across requests; contains no resolved user or resource output. */
final class SubjectRegistry
{
    /** @var array<string, SubjectDefinition> */
    private array $definitions = [];

    private bool $finalized = false;

    /** @internal Resolved from the container as a singleton. */
    public function __construct(private readonly PageRegistry $pages) {}

    /**
     * Snapshot subject kinds without evaluating callbacks.
     *
     * Complete registration before any read finalizes the registry.
     *
     * @throws InvalidDefinition If finalized, or a kind's name is empty or already used, or its class or label is missing.
     */
    public function register(Subject ...$subjects): void
    {
        if ($this->finalized) {
            throw new InvalidDefinition('Subjects must be registered before the subject registry is resolved.');
        }
        $pending = [];
        foreach ($subjects as $subject) {
            if (trim($subject->name) === '' || isset($pending[$subject->name]) || isset($this->definitions[$subject->name])) {
                throw new InvalidDefinition("Subject '{$subject->name}' must have a unique, non-empty name.");
            }
            $pending[$subject->name] = $subject->toDefinition();
        }

        $this->definitions += $pending;
    }

    /**
     * Validate kinds against each other and the finalized page registry, then freeze.
     *
     * @internal All registry reads invoke this automatically; repeated calls are
     * harmless. Laravel-specific checks (routable classes, route parameters) run
     * in the Subjects reader.
     *
     * @throws InvalidDefinition For overlapping classes,
     *                           unknown or non-presenting canonical pages, or pages presenting unknown kinds.
     */
    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $pages = [];
        foreach ($this->pages->all() as $page) {
            $pages[$page->id] = $page;
        }

        $checked = [];
        foreach ($this->definitions as $kind) {
            foreach ($checked as $other) {
                if (is_a($kind->class, $other->class, true) || is_a($other->class, $kind->class, true)) {
                    throw new InvalidDefinition("Subjects '{$other->name}' and '{$kind->name}' have overlapping classes.");
                }
            }
            $checked[] = $kind;

            if ($kind->pageId !== null) {
                $page = $pages[$kind->pageId] ?? throw new InvalidDefinition("Subject '{$kind->name}' references unknown page '{$kind->pageId}'.");
                if ($page->presents !== $kind->name) {
                    throw new InvalidDefinition("Subject '{$kind->name}' names page '{$page->id}', which must present it.");
                }
            }
        }

        foreach ($pages as $page) {
            if ($page->presents !== null && ! isset($this->definitions[$page->presents])) {
                throw new InvalidDefinition("Page '{$page->id}' presents unknown subject '{$page->presents}'.");
            }
        }

        $this->finalized = true;
    }

    /**
     * Read every kind in registration order.
     *
     * @return list<SubjectDefinition>
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function all(): array
    {
        $this->finalize();

        return array_values($this->definitions);
    }

    /**
     * Find a kind by name without evaluating its callbacks.
     *
     * @throws InvalidDefinition If finalization fails.
     * @throws ResolutionFailed If the kind is unknown.
     */
    public function kind(string $name): SubjectDefinition
    {
        $this->finalize();

        return $this->definitions[$name] ?? throw new ResolutionFailed("Unknown subject '{$name}'.");
    }

    /**
     * Find the kind whose class the object is an instance of; null if none.
     *
     * @internal Read by Subjects.
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function forObject(object $object): ?SubjectDefinition
    {
        foreach ($this->all() as $kind) {
            if ($object instanceof $kind->class) {
                return $kind;
            }
        }

        return null;
    }

    /**
     * Read the pages that present a kind, in registration order.
     *
     * @internal Read by the inspection commands.
     *
     * @return list<PageDefinition>
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function presentingPages(string $name): array
    {
        $this->finalize();

        return array_values(array_filter($this->pages->all(), fn (PageDefinition $page): bool => $page->presents === $name));
    }
}
