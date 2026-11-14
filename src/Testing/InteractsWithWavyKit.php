<?php

declare(strict_types=1);

namespace WavyKit\Testing;

use PHPUnit\Framework\Assert;
use WavyKit\Facades\Current;
use WavyKit\Navigation\MenuItem;

/**
 * Assertions about the last test request's page, breadcrumbs, and menus.
 *
 * Add the trait to your application's TestCase, make a request, then assert:
 * `$this->get('/projects/42'); $this->assertCurrentPage('projects.show');`.
 * Assertions read the application's state after the request through
 * WavyKit\Facades\Current, so callbacks see that request's bound models and
 * user. Requires PHPUnit, which Laravel and Pest test suites already provide.
 */
trait InteractsWithWavyKit
{
    /** Assert the active page's ID; null asserts that no page is active. */
    public function assertCurrentPage(?string $id): static
    {
        $actual = Current::pageId();
        Assert::assertSame($id, $actual, sprintf(
            'Expected the current page to be %s, but it was %s.',
            $id === null ? 'none' : "'{$id}'",
            $actual === null ? 'none' : "'{$actual}'",
        ));

        return $this;
    }

    /**
     * Assert the breadcrumb labels, root first and in order.
     *
     * @param  list<string>  $labels
     */
    public function assertBreadcrumbs(array $labels): static
    {
        $actual = array_map(fn ($crumb): string => $crumb->label, Current::breadcrumbs());
        Assert::assertSame($labels, $actual, sprintf(
            'Expected breadcrumbs [%s], but they were [%s].',
            implode(' > ', $labels),
            implode(' > ', $actual),
        ));

        return $this;
    }

    /** Assert that a menu shows a page at any depth for this request. */
    public function assertMenuHas(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        Assert::assertNotNull(
            $this->findWavyKitMenuItem($items, $pageId),
            "Expected menu '{$menu}' to show page '{$pageId}'.\n".$this->describeWavyKitMenu($items),
        );

        return $this;
    }

    /** Assert that a menu does not show a page at any depth for this request. */
    public function assertMenuMissing(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        Assert::assertNull(
            $this->findWavyKitMenuItem($items, $pageId),
            "Expected menu '{$menu}' not to show page '{$pageId}'.\n".$this->describeWavyKitMenu($items),
        );

        return $this;
    }

    /** Assert that a menu shows a page and marks it active for this request. */
    public function assertMenuActive(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        $item = $this->findWavyKitMenuItem($items, $pageId);
        Assert::assertTrue(
            $item?->active === true,
            sprintf(
                "Expected page '%s' to be active in menu '%s', but it was %s.\n%s",
                $pageId,
                $menu,
                $item === null ? 'not in the menu' : 'inactive',
                $this->describeWavyKitMenu($items),
            ),
        );

        return $this;
    }

    /** @param list<MenuItem> $items */
    private function findWavyKitMenuItem(array $items, string $pageId): ?MenuItem
    {
        foreach ($items as $item) {
            if ($item->pageId === $pageId) {
                return $item;
            }
            $found = $this->findWavyKitMenuItem($item->children, $pageId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /** @param list<MenuItem> $items */
    private function describeWavyKitMenu(array $items, int $depth = 0): string
    {
        if ($items === [] && $depth === 0) {
            return 'The menu is empty.';
        }
        $lines = $depth === 0 ? "The menu was:\n" : '';
        foreach ($items as $item) {
            $lines .= str_repeat('  ', $depth + 1).'- '.$item->label
                .' ('.($item->pageId ?? 'group').')'
                .($item->active ? ' [active]' : '')
                ."\n"
                .$this->describeWavyKitMenu($item->children, $depth + 1);
        }

        return $lines;
    }
}
