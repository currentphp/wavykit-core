<?php

declare(strict_types=1);

namespace WavyKit\Console;

use Illuminate\Console\Command;

/**
 * Lists registered pages as a tree, root pages first.
 *
 * @internal Run as `php artisan wavykit:pages`; the command name and output are the supported interface.
 *
 * @phpstan-import-type PageNode from Inspector
 */
final class PagesCommand extends Command
{
    /** @var string */
    protected $signature = 'wavykit:pages {--json : Print the page tree as JSON}';

    /** @var string */
    protected $description = 'List registered WavyKit pages as a tree';

    public function handle(Inspector $inspector): int
    {
        $problems = $inspector->problems();
        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->components->error($problem);
            }

            return self::FAILURE;
        }

        $pages = $inspector->pages();
        if ($this->option('json') === true) {
            $this->line(json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        if ($pages === []) {
            $this->components->info('No pages are registered.');

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
            ]);
            $this->components->twoColumnDetail(str_repeat('  ', $depth).$page['id'], implode(' · ', $details));
            $this->render($page['children'], $depth + 1);
        }
    }
}
