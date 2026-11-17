<?php

declare(strict_types=1);

namespace WavyKit\Console;

/**
 * Lists registered pages as a tree, root pages first.
 *
 * @internal Run as `php artisan wavykit:pages`; the command name and output are the supported interface.
 *
 * @phpstan-import-type PageNode from Inspector
 */
final class PagesCommand extends InspectionCommand
{
    /** @var string */
    protected $signature = 'wavykit:pages {--json : Print the page tree as JSON}';

    /** @var string */
    protected $description = 'List registered WavyKit pages as a tree';

    public function handle(Inspector $inspector): int
    {
        if ($this->reportProblems($inspector)) {
            return self::FAILURE;
        }

        $pages = $inspector->pages();
        if ($this->printedAsJsonOrEmpty($pages, 'No pages are registered.')) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->render($pages, 0);
        $this->newLine();

        return self::SUCCESS;
    }

    /** @param list<mixed> $pages */
    private function render(array $pages, int $depth): void
    {
        /** @var list<PageNode> $pages */
        foreach ($pages as $page) {
            $details = array_filter([
                $page['label'] ?? '(dynamic)',
                $page['route'] === null ? 'no route' : 'route '.$page['route'],
                $page['icon'] === null ? null : 'icon '.$page['icon'],
                $page['presents'] === null ? null : 'presents '.$page['presents'],
            ]);
            // Written directly: the two-column component trims the indentation.
            $this->line('  '.str_repeat('  ', $depth).$page['id'].'  <fg=gray>'.implode(' · ', $details).'</>');
            $this->render($page['children'], $depth + 1);
        }
    }
}
