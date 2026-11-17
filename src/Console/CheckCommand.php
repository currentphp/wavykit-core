<?php

declare(strict_types=1);

namespace WavyKit\Console;

/**
 * Validates every page and menu definition, for CI.
 *
 * @internal Run as `php artisan wavykit:check`; the command name, output, and exit code are the supported interface.
 */
final class CheckCommand extends InspectionCommand
{
    /** @var string */
    protected $signature = 'wavykit:check';

    /** @var string */
    protected $description = 'Check WavyKit page, menu, and subject definitions; exits 1 on any problem';

    public function handle(Inspector $inspector): int
    {
        if ($this->reportProblems($inspector)) {
            return self::FAILURE;
        }

        $pages = $inspector->pageCount();
        $menus = count($inspector->menuNames());
        $subjects = count($inspector->subjects());
        $this->components->info(sprintf(
            'WavyKit definitions are valid: %d %s, %d %s, %d %s.',
            $pages,
            $pages === 1 ? 'page' : 'pages',
            $menus,
            $menus === 1 ? 'menu' : 'menus',
            $subjects,
            $subjects === 1 ? 'subject' : 'subjects',
        ));

        return self::SUCCESS;
    }
}
