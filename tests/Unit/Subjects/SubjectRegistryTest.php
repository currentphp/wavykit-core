<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Exceptions\ResolutionFailed;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;
use WavyKit\Subjects\Subject;
use WavyKit\Subjects\SubjectRegistry;
use WavyKit\Tests\Fixtures\Project;
use WavyKit\Tests\Fixtures\Task;

function subjectsWith(Page ...$pages): SubjectRegistry
{
    $registry = new PageRegistry;
    $registry->register(...$pages);

    return new SubjectRegistry($registry);
}

function projectKind(): Subject
{
    return Subject::make('project')->for(Project::class)->label(fn (Project $project): string => 'P');
}

test('kinds register in order and are found by name', function () {
    $subjects = subjectsWith(Page::make('projects.show')->route('projects.show')->presents('project'));
    $subjects->register(projectKind()->page('projects.show'), Subject::make('task')->for(Task::class)->label(fn (): string => 'T'));

    expect(array_map(fn ($kind) => $kind->name, $subjects->all()))->toBe(['project', 'task'])
        ->and($subjects->kind('project')->pageId)->toBe('projects.show')
        ->and(fn () => $subjects->kind('missing'))->toThrow(ResolutionFailed::class, "Unknown subject 'missing'.");
});

test('registration rejects invalid kinds', function (Closure $register, string $message) {
    $subjects = subjectsWith(
        Page::make('projects.show')->route('projects.show')->presents('project'),
        Page::make('plain')->route('plain'),
    );

    expect(function () use ($subjects, $register) {
        $register($subjects);
        $subjects->finalize();
    })->toThrow(InvalidDefinition::class, $message);
})->with([
    'empty name' => [fn (SubjectRegistry $s) => $s->register(Subject::make(' ')), "Subject ' ' must have a unique, non-empty name."],
    'duplicate name' => [fn (SubjectRegistry $s) => $s->register(projectKind(), projectKind()), "Subject 'project' must have a unique, non-empty name."],
    'no class' => [fn (SubjectRegistry $s) => $s->register(Subject::make('project')->label(fn (): string => 'P')), "Subject 'project' must name an existing class with for()."],
    'no label' => [fn (SubjectRegistry $s) => $s->register(Subject::make('project')->for(Project::class)), "Subject 'project' must have a label()."],
    'overlapping classes' => [fn (SubjectRegistry $s) => $s->register(projectKind(), Subject::make('model')->for(Model::class)->label(fn (): string => 'M')), "Subjects 'project' and 'model' have overlapping classes."],
    'unknown canonical page' => [fn (SubjectRegistry $s) => $s->register(projectKind()->page('missing')), "Subject 'project' references unknown page 'missing'."],
    'canonical page does not present it' => [fn (SubjectRegistry $s) => $s->register(projectKind()->page('plain')), "Subject 'project' names page 'plain', which must present it."],
    'page presents an unknown kind' => [fn (SubjectRegistry $s) => null, "Page 'projects.show' presents unknown subject 'project'."],
]);

test('kinds cannot be registered after the registry is read', function () {
    $subjects = subjectsWith();
    $subjects->all();

    expect(fn () => $subjects->register(projectKind()))
        ->toThrow(InvalidDefinition::class, 'Subjects must be registered before the subject registry is resolved.');
});
