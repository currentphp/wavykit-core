<?php

declare(strict_types=1);

namespace WavyKit\Console;

use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Navigation\MenuEntryDefinition;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\PageDefinition;
use WavyKit\Pages\PageRegistry;
use WavyKit\Pages\Pages;
use WavyKit\Subjects\SubjectDefinition;
use WavyKit\Subjects\SubjectRegistry;
use WavyKit\Subjects\Subjects;

/**
 * Describes registered pages and menus as plain data, without a request.
 *
 * Callback labels are reported as dynamic and never run.
 *
 * @internal Shared by the wavykit:pages, wavykit:menus, and wavykit:check commands.
 *
 * @phpstan-type PageNode array{id: string, label: ?string, dynamicLabel: bool, route: ?string, routeParameters: bool, icon: ?string, presents: ?string, children: list<mixed>}
 * @phpstan-type SubjectDescription array{name: string, class: ?string, page: ?string, routeParameters: bool, presentedBy: list<string>}
 * @phpstan-type MenuNode array{kind: 'page'|'group', id: string, label: ?string, dynamicLabel: bool, icon: ?string, visibility: bool, children: list<mixed>}
 * @phpstan-type MenuDescription array{name: string, kind: 'derived'|'defined', roots: list<string>, except: list<string>, entries: list<MenuNode>}
 */
final readonly class Inspector
{
    public function __construct(
        private PageRegistry $registry,
        private MenuRegistry $menus,
        private Pages $pages,
        private SubjectRegistry $subjectRegistry,
        private Subjects $subjects,
    ) {}

    /**
     * Every definition problem: the first registry error, or every unknown route.
     *
     * Registries stop at their first invalid definition, so fix and rerun to
     * see the next one. Menus are checked only when pages are valid.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        try {
            $this->registry->finalize();
        } catch (InvalidDefinition $exception) {
            return [$exception->getMessage()];
        }

        $problems = $this->pages->unknownRoutes();
        try {
            $this->menus->finalize();
        } catch (InvalidDefinition $exception) {
            $problems[] = $exception->getMessage();
        }
        try {
            $this->subjectRegistry->finalize();
        } catch (InvalidDefinition $exception) {
            $problems[] = $exception->getMessage();

            return $problems;
        }

        return [...$problems, ...$this->subjects->problems()];
    }

    public function pageCount(): int
    {
        return count($this->registry->all());
    }

    /** @return list<PageNode> */
    public function pages(): array
    {
        return array_map($this->page(...), $this->registry->children(null));
    }

    /** @return list<SubjectDescription> */
    public function subjects(): array
    {
        return array_map(fn (SubjectDefinition $kind): array => [
            'name' => $kind->name,
            'class' => $kind->class,
            'page' => $kind->pageId,
            'routeParameters' => $kind->routeParameters !== null,
            'presentedBy' => array_map(fn (PageDefinition $page): string => $page->id, $this->subjectRegistry->presentingPages($kind->name)),
        ], $this->subjectRegistry->all());
    }

    /** @return list<string> */
    public function menuNames(): array
    {
        return $this->menus->names();
    }

    /** @return MenuDescription */
    public function menu(string $name): array
    {
        $entries = $this->menus->entries($name);
        if ($entries !== null) {
            return [
                'name' => $name,
                'kind' => 'defined',
                'roots' => [],
                'except' => [],
                'entries' => array_map($this->entry(...), $entries),
            ];
        }

        $except = $this->menus->exclusions($name);
        $roots = array_values(array_filter(
            $this->menus->roots($name),
            fn (PageDefinition $page): bool => ! in_array($page->id, $except, true),
        ));

        return [
            'name' => $name,
            'kind' => 'derived',
            'roots' => array_map(fn (PageDefinition $page): string => $page->id, $this->menus->roots($name)),
            'except' => $except,
            'entries' => array_map(fn (PageDefinition $page): array => $this->derivedEntry($page, $except), $roots),
        ];
    }

    /** @return PageNode */
    private function page(PageDefinition $page): array
    {
        return [
            'id' => $page->id,
            // A page that presents a subject without its own label takes the subject's, per request.
            'label' => is_string($page->label) && ($page->presents === null || $page->labelled) ? $page->label : null,
            'dynamicLabel' => ! is_string($page->label) || ($page->presents !== null && ! $page->labelled),
            'route' => $page->routeName,
            'routeParameters' => $page->routeParameters !== null,
            'icon' => $page->icon,
            'presents' => $page->presents,
            'children' => array_map($this->page(...), $this->registry->children($page->id)),
        ];
    }

    /**
     * @param  list<string>  $except
     * @return MenuNode
     */
    private function derivedEntry(PageDefinition $page, array $except): array
    {
        $children = array_values(array_filter(
            $this->registry->children($page->id),
            fn (PageDefinition $child): bool => ! in_array($child->id, $except, true),
        ));

        return [
            'kind' => 'page',
            'id' => $page->id,
            'label' => is_string($page->label) ? $page->label : null,
            'dynamicLabel' => ! is_string($page->label),
            'icon' => $page->icon,
            'visibility' => false,
            'children' => array_map(fn (PageDefinition $child): array => $this->derivedEntry($child, $except), $children),
        ];
    }

    /** @return MenuNode */
    private function entry(MenuEntryDefinition $entry): array
    {
        $page = $entry->isGroup ? null : $this->registry->page($entry->id);
        $label = $entry->label ?? $page->label ?? $entry->id;

        return [
            'kind' => $entry->isGroup ? 'group' : 'page',
            'id' => $entry->id,
            'label' => is_string($label) ? $label : null,
            'dynamicLabel' => ! is_string($label),
            'icon' => $entry->icon ?? $page?->icon,
            'visibility' => $entry->visibility !== null,
            'children' => array_map($this->entry(...), $entry->children),
        ];
    }
}
