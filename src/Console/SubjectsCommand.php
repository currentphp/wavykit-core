<?php

declare(strict_types=1);

namespace WavyKit\Console;

/**
 * Lists registered subject kinds with their class, canonical page, and presenting pages.
 *
 * @internal Run as `php artisan wavykit:subjects`; the command name and output are the supported interface.
 */
final class SubjectsCommand extends InspectionCommand
{
    /** @var string */
    protected $signature = 'wavykit:subjects {--json : Print the subject kinds as JSON}';

    /** @var string */
    protected $description = 'List registered WavyKit subject kinds';

    public function handle(Inspector $inspector): int
    {
        if ($this->reportProblems($inspector)) {
            return self::FAILURE;
        }

        $subjects = $inspector->subjects();
        if ($this->printedAsJsonOrEmpty($subjects, 'No subjects are registered.')) {
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
