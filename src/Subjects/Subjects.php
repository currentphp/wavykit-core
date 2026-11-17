<?php

declare(strict_types=1);

namespace WavyKit\Subjects;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Router;
use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Exceptions\ResolutionFailed;
use WavyKit\Pages\PageRegistry;
use WavyKit\Pages\Pages;
use WavyKit\Support\RouteParameters;

/**
 * Resolves subjects: any object's subject, and the current request's.
 *
 * Reads objects the application has bound; never loads or queries them.
 */
final class Subjects
{
    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private readonly SubjectRegistry $registry,
        private readonly PageRegistry $pages,
        private readonly Pages $reader,
        private readonly Router $router,
    ) {}

    /**
     * Resolve the subject for any object of a registered kind, with its canonical URL.
     *
     * Works outside a request (queue jobs, notifications, commands) wherever
     * Laravel can generate URLs.
     *
     * @throws InvalidDefinition If definitions are invalid.
     * @throws ResolutionFailed If no kind accepts the object, or its label, route key, or URL cannot be resolved.
     */
    public function of(object $object): SubjectItem
    {
        $this->validate();
        $kind = $this->registry->forObject($object)
            ?? throw new ResolutionFailed('No subject kind accepts '.$object::class.'.');

        return $this->item($kind, $object);
    }

    /**
     * Resolve the subject the current request is about: the active page's, or
     * else the nearest ancestor page's that presents one; null when none does
     * or outside a request.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the presented route parameter is not a bound object, or the subject cannot be resolved.
     */
    public function current(): ?SubjectItem
    {
        $context = $this->reader->context();
        foreach (array_reverse($this->reader->activeTrail($context)) as $page) {
            if ($page->presents === null) {
                continue;
            }
            $this->validate();

            return $this->item($this->registry->kind($page->presents), $this->reader->presentedObject($page, $context));
        }

        return null;
    }

    /**
     * Describe every problem the registry cannot check without Laravel: kinds
     * whose class is not routable, and presenting pages whose route lacks the parameter.
     *
     * @internal Read by wavykit:check; readers throw the first problem.
     *
     * @return list<string>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function problems(): array
    {
        $problems = [];
        foreach ($this->registry->all() as $kind) {
            if (! is_a($kind->class, UrlRoutable::class, true)) {
                $problems[] = "Subject '{$kind->name}' class {$kind->class} must be routable (implement UrlRoutable) to have an identity.";
            }
        }
        foreach ($this->pages->all() as $page) {
            if ($page->presents === null) {
                continue;
            }
            $parameter = (string) $page->presentsParameter;
            $route = $page->routeName === null ? null : $this->router->getRoutes()->getByName($page->routeName);
            if ($page->routeName !== null && $route === null) {
                continue; // Reported as an unknown route by Pages.
            }
            if ($route === null || ! in_array($parameter, $route->parameterNames(), true)) {
                $problems[] = "Page '{$page->id}' presents subject '{$page->presents}', but its route has no '{$parameter}' parameter.";
            }
        }

        return $problems;
    }

    /** @throws InvalidDefinition */
    private function validate(): void
    {
        $problems = $this->problems();
        if ($problems !== []) {
            throw new InvalidDefinition($problems[0]);
        }
    }

    /** @throws ResolutionFailed */
    private function item(SubjectDefinition $kind, object $object): SubjectItem
    {
        if (! $object instanceof UrlRoutable) {
            throw new ResolutionFailed("Subject '{$kind->name}' needs a routable object for its identity.");
        }
        $key = $object->getRouteKey();
        if (! is_scalar($key)) {
            throw new ResolutionFailed("Subject '{$kind->name}' has a route key that is not a scalar.");
        }

        return new SubjectItem(
            $kind->name,
            (string) $key,
            $this->reader->subjectLabel($kind, $object),
            $this->url($kind, $object),
        );
    }

    /** @throws ResolutionFailed */
    private function url(SubjectDefinition $kind, object $object): ?string
    {
        if ($kind->pageId === null) {
            return null;
        }
        $parameters = $kind->routeParameters === null
            ? [$kind->name => $object]
            : RouteParameters::resolve($kind->routeParameters, $object, "subject '{$kind->name}'");

        return $this->reader->urlWith($this->pages->page($kind->pageId), $parameters);
    }
}
