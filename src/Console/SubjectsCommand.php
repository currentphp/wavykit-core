<?php

declare(strict_types=1);

namespace WavyKit\Console;

use Illuminate\Console\Command;

/**
 * Lists registered subject kinds with their class, canonical page, and presenting pages.
 *
 * @internal Run as `php artisan wavykit:subjects`; the command name and output are the supported interface.
 */
final class SubjectsCommand extends Command
{
    /** @var string */
    protected $signature = 'wavykit:subjects {--json : Print the subject kinds as JSON}';

    /** @var string */
    protected $description = 'List registered WavyKit subject kinds';

    public function handle(Inspector $inspector): int
    {
        $problems = $inspector->problems();
        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->components->error($problem);
            }

            return self::FAILURE;
        }

        $subjects = $inspector->subjects();
        if ($this->option('json') === true) {
            $this->line(json_encode($subjects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        if ($subjects === []) {
            $this->components->info('No subjects are registered.');

            return self::SUCCESS;
        }

        $this->newLine();
        foreach ($subjects as $subject) {
            $details = array_filter([
                $subject['class'],
                $subject['page'] === null ? 'no page' : 'page '.$subject['page'],
                $subject['presentedBy'] === [] ? null : 'presented by '.implode(', ', $subject['presentedBy']),
            ]);
            $this->line('  '.$subject['name'].'  <fg=gray>'.implode(' · ', $details).'</>');
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
