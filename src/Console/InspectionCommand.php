<?php

declare(strict_types=1);

namespace WavyKit\Console;

use Illuminate\Console\Command;

/**
 * Shared steps for the wavykit:* commands: report definition problems, and
 * print listings as JSON or say that nothing is registered.
 *
 * @internal Base of the inspection commands.
 */
abstract class InspectionCommand extends Command
{
    /** Print each problem as an error; true when there were any. */
    protected function reportProblems(Inspector $inspector): bool
    {
        $problems = $inspector->problems();
        foreach ($problems as $problem) {
            $this->components->error($problem);
        }

        return $problems !== [];
    }

    /**
     * Print a listing as JSON with --json, or a message when it is empty;
     * true when either was printed and the command is done.
     *
     * @param  list<mixed>  $listing
     */
    protected function printedAsJsonOrEmpty(array $listing, string $emptyMessage): bool
    {
        if ($this->option('json') === true) {
            $this->line(json_encode($listing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return true;
        }
        if ($listing === []) {
            $this->components->info($emptyMessage);

            return true;
        }

        return false;
    }
}
