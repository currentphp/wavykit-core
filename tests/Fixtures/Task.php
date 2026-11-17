<?php

declare(strict_types=1);

namespace WavyKit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/** Synthetic bound model belonging to a project; no database operations. */
final class Task extends Model
{
    public Project $project;

    protected $guarded = [];

    public static function in(Project $project, array $attributes): self
    {
        $task = new self($attributes);
        $task->project = $project;

        return $task;
    }
}
