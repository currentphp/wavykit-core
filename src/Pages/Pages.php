<?php

declare(strict_types=1);

namespace WavyKit\Pages;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Throwable;
use WavyKit\Context;
use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Exceptions\ResolutionFailed;
use WavyKit\Subjects\SubjectDefinition;
use WavyKit\Subjects\SubjectRegistry;
use WavyKit\Support\Label;
use WavyKit\Support\RouteParameters;

/** Resolves registered pages for the current Laravel request; does not create routes or enforce access. */
final class Pages
{
    /** Route references are checked once per request or job; registries are frozen by then. */
    private bool $routesChecked = false;

    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private readonly PageRegistry $registry,
        private readonly Router $router,
        private readonly UrlGenerator $urls,
        private readonly SubjectRegistry $subjects,
    ) {}

    /**
     * Resolve the current request's exact named-route association.
     *
     * Returns null for unmatched or unregistered routes; callbacks remain unevaluated.
     * Resolve after routes have loaded. This validates all registered route references.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     */
    public function current(): ?PageDefinition
    {
        $this->validateRoutes();

        return $this->registry->forRoute($this->activeRoute()?->getName());
    }

    /**
     * Snapshot the active page, bound route parameters, and nullable current user.
     *
     * Resolve after binding middleware when callbacks require models. Do not retain
     * the context across requests or on application singletons.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If route parameters or the current user are invalid.
     */
    public function context(): Context
    {
        $parameters = [];
        foreach ($this->activeRoute()?->parameters() ?? [] as $name => $value) {
            if (! is_string($name)) {
                throw new ResolutionFailed('Active route parameters must be keyed by name.');
            }
            $parameters[$name] = $value;
        }

        $user = $this->router->current() === null ? null : $this->router->getCurrentRequest()->user();
        if ($user !== null && ! $user instanceof Authenticatable) {
            throw new ResolutionFailed('The current request user must implement Authenticatable.');
        }

        return new Context($this->current(), $parameters, $user);
    }

    /**
     * Read the active page's ancestry, root first; empty when no page is active.
     *
     * @return list<PageDefinition>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function activeTrail(Context $context): array
    {
        return $context->page === null ? [] : $this->registry->trail($context->page->id);
    }

    /**
     * Resolve a page's label, running its callback with the given context.
     *
     * A page that presents a subject and has no label of its own takes the
     * subject's label, from the context's bound route parameter.
     *
     * @throws InvalidDefinition If subject definitions are invalid.
     * @throws ResolutionFailed If a callback fails or returns a non-string, or the presented subject is not bound.
     */
    public function label(PageDefinition $page, Context $context): string
    {
        if ($page->presents === null || $page->labelled) {
            return Label::resolve($page->label, $context, "page '{$page->id}'");
        }

        return $this->subjectLabel($this->subjects->kind($page->presents), $this->presentedObject($page, $context));
    }

    /**
     * Read the bound object a presenting page shows, from the context's route parameters.
     *
     * @internal Shared by label() and the Subjects reader.
     *
     * @throws ResolutionFailed If the page presents nothing, or the parameter is not a bound object.
     */
    public function presentedObject(PageDefinition $page, Context $context): object
    {
        $parameter = $page->presentsParameter ?? throw new ResolutionFailed("Page '{$page->id}' presents no subject.");
        $object = $context->parameters()[$parameter] ?? null;

        return is_object($object)
            ? $object
            : throw new ResolutionFailed("Page '{$page->id}' presents subject '{$page->presents}', but route parameter '{$parameter}' is not a bound object.");
    }

    /**
     * Resolve a subject's label from its object.
     *
     * @internal Shared by label() and the Subjects reader.
     *
     * @throws ResolutionFailed If the callback throws or does not return a string.
     */
    public function subjectLabel(SubjectDefinition $kind, object $object): string
    {
        return Label::resolve($kind->label, $object, "subject '{$kind->name}'");
    }

    /**
     * Generate a page's URL, or null for a route-less page.
     *
     * Forwards only parameters the destination route declares, from the page's
     * route-parameter mapping when present, otherwise from the active route.
     *
     * @throws InvalidDefinition If the page references an unknown route.
     * @throws ResolutionFailed If the mapping is invalid or URL generation fails.
     */
    public function url(PageDefinition $page, Context $context): ?string
    {
        if ($page->routeName === null) {
            return null;
        }
        $parameters = $page->routeParameters === null
            ? $context->parameters()
            : RouteParameters::resolve($page->routeParameters, $context, "page '{$page->id}'");

        return $this->urlWith($page, $parameters);
    }

    /**
     * Generate a page's URL from explicit parameters, forwarding only those its route declares.
     *
     * @internal Shared by url() and the Subjects reader.
     *
     * @param  array<string, mixed>  $activeParameters
     *
     * @throws InvalidDefinition If the page references an unknown route.
     * @throws ResolutionFailed If URL generation fails.
     */
    public function urlWith(PageDefinition $page, array $activeParameters): ?string
    {
        if ($page->routeName === null) {
            return null;
        }
        $route = $this->router->getRoutes()->getByName($page->routeName)
            ?? throw new InvalidDefinition("Page '{$page->id}' references unknown route '{$page->routeName}'.");
        $parameters = [];
        foreach ($route->parameterNames() as $name) {
            if (! is_string($name)) {
                throw new InvalidDefinition("Route '{$page->routeName}' contains an invalid parameter name.");
            }
            if (array_key_exists($name, $activeParameters)) {
                $parameters[$name] = $activeParameters[$name];
            }
        }

        try {
            return $this->urls->route($page->routeName, $parameters);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not generate URL for page '{$page->id}' and route '{$page->routeName}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * Describe every page whose route is not registered, in registration order.
     *
     * @internal Read by wavykit:check, which reports every problem at once.
     *
     * @return list<string>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function unknownRoutes(): array
    {
        $problems = [];
        foreach ($this->registry->all() as $page) {
            if ($page->routeName !== null && $this->router->getRoutes()->getByName($page->routeName) === null) {
                $problems[] = "Page '{$page->id}' references unknown route '{$page->routeName}'.";
            }
        }

        return $problems;
    }

    private function validateRoutes(): void
    {
        if ($this->routesChecked) {
            return;
        }
        $problems = $this->unknownRoutes();
        if ($problems !== []) {
            throw new InvalidDefinition($problems[0]);
        }
        $this->routesChecked = true;
    }

    private function activeRoute(): ?Route
    {
        if ($this->router->current() === null) {
            return null;
        }

        // Older router versions retain their previous route after an unmatched request.
        return $this->router->getCurrentRequest()->route();
    }
}
