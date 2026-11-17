<?php

declare(strict_types=1);

namespace WavyKit\Console;

use Illuminate\Console\Command;

/**
 * Validates every page and menu definition, for CI.
 *
 * @internal Run as `php artisan wavykit:check`; the command name, output, and exit code are the supported interface.
 */
final class CheckCommand extends Command
{
    /** @var string */
    protected $signature = 'wavykit:check';

    /** @var string */
    protected $description = 'Check WavyKit page, menu, and subject definitions; exits 1 on any problem';

    public function handle(Inspector $inspector): int
    {
        $problems = $inspector->problems();
        foreach ($problems as $problem) {
            $this->components->error($problem);
        }
        if ($problems !== []) {
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
