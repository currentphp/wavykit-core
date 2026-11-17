<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use WavyKit\Exceptions\InvalidDefinition;
use WavyKit\Exceptions\ResolutionFailed;
use WavyKit\Facades\Current;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;
use WavyKit\Subjects\Subject;
use WavyKit\Subjects\SubjectRegistry;
use WavyKit\Subjects\Subjects;
use WavyKit\Testing\InteractsWithWavyKit;
use WavyKit\Tests\Fixtures\Project;
use WavyKit\Tests\Fixtures\Task;

uses(InteractsWithWavyKit::class);

function apollo(): Project
{
    return new Project(['id' => 42, 'name' => 'Apollo']);
}

function fixLogin(): Task
{
    return Task::in(apollo(), ['id' => 7, 'title' => 'Fix login']);
}

beforeEach(function () {
    Route::bind('project', fn (string $id): Project => $id === '42' ? apollo() : abort(404));
    Route::bind('item', fn (string $id): Task => $id === '7' ? fixLogin() : abort(404));
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('/projects', fn () => 'index')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'show')->name('projects.show');
        Route::get('/projects/{project}/edit', fn () => 'edit')->name('projects.edit');
        Route::get('/projects/{project}/tasks/{item}', fn () => 'task')->name('tasks.show');
    });

    app(SubjectRegistry::class)->register(
        Subject::make('project')->for(Project::class)
            ->label(fn (Project $project): string => (string) $project->getAttribute('name'))
            ->page('projects.show'),
        Subject::make('task')->for(Task::class)
            ->label(fn (Task $task): string => (string) $task->getAttribute('title'))
            ->page('tasks.show', fn (Task $task): array => ['project' => $task->project, 'item' => $task]),
    );
    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')->presents('project')->children(
                Page::make('projects.edit')->route('projects.edit')->label('Edit'),
                Page::make('projects.tasks')->label('Tasks')->children(
                    Page::make('tasks.show')->route('tasks.show')->presents('task', parameter: 'item'),
                ),
            ),
        ),
    );
});

test('pages that present a subject take its label; explicit labels win', function () {
    $this->get('/projects/42/tasks/7')->assertOk();
    $this->assertBreadcrumbs(['Projects', 'Apollo', 'Tasks', 'Fix login']);

    $this->get('/projects/42/edit')->assertOk();
    $this->assertBreadcrumbs(['Projects', 'Apollo', 'Edit']);
});

test('the current subject is the active page subject, or the nearest ancestor subject', function () {
    $this->get('/projects/42/tasks/7')->assertOk();
    $this->assertCurrentSubject('task', '7');
    expect(Current::subject()?->url)->toBe('http://localhost/projects/42/tasks/7');

    $this->get('/projects/42/edit')->assertOk();
    $this->assertCurrentSubject('project', '42');
    expect(Current::subject()?->label)->toBe('Apollo');

    $this->get('/projects')->assertOk();
    $this->assertCurrentSubject(null);
});

test('there is no current subject outside a request', function () {
    expect(Current::subject())->toBeNull();
});

test('any object resolves to its subject and canonical URL', function () {
    $task = app(Subjects::class)->of(fixLogin());
    $project = app(Subjects::class)->of(apollo());

    expect($task->key())->toBe('task:7')
        ->and($task->label)->toBe('Fix login')
        ->and($task->url)->toBe('http://localhost/projects/42/tasks/7')
        ->and($project->url)->toBe('http://localhost/projects/42')
        ->and(json_decode(json_encode($task, JSON_THROW_ON_ERROR), true))->toBe([
            'kind' => 'task',
            'id' => '7',
            'label' => 'Fix login',
            'url' => 'http://localhost/projects/42/tasks/7',
        ]);
});

test('a kind without a page has no URL', function () {
    app(SubjectRegistry::class)->register(
        Subject::make('note')->for(User::class)->label(fn (): string => 'A note'),
    );
    $note = new User;
    $note->forceFill(['id' => 3]);

    expect(app(Subjects::class)->of($note)->url)->toBeNull();
});

test('objects of no registered kind are rejected', function () {
    expect(fn () => app(Subjects::class)->of(new stdClass))
        ->toThrow(ResolutionFailed::class, 'No subject kind accepts stdClass.');
});

test('a presented parameter that is not a bound object fails clearly', function () {
    Route::get('/raw/{id}', fn () => Current::subject()?->key() ?? 'none')->name('raw');
    app(PageRegistry::class)->register(Page::make('raw')->route('raw')->presents('project', parameter: 'id'));
    $this->withoutExceptionHandling();

    $this->get('/raw/42');
})->throws(ResolutionFailed::class, "Page 'raw' presents subject 'project', but route parameter 'id' is not a bound object.");

test('reader checks report unroutable classes and missing route parameters', function () {
    Route::get('/wrong/{other}', fn () => 'wrong')->name('wrong');
    app(SubjectRegistry::class)->register(Subject::make('plain')->for(ArrayObject::class)->label(fn (): string => 'Plain'));
    app(PageRegistry::class)->register(
        Page::make('wrong')->route('wrong')->presents('project'),
        Page::make('routeless')->presents('task'),
    );

    expect(app(Subjects::class)->problems())->toBe([
        'Subject \'plain\' class ArrayObject must be routable (implement UrlRoutable) to have an identity.',
        "Page 'wrong' presents subject 'project', but its route has no 'project' parameter.",
        "Page 'routeless' presents subject 'task', but its route has no 'task' parameter.",
    ]);
    expect(fn () => app(Subjects::class)->of(apollo()))
        ->toThrow(InvalidDefinition::class, 'must be routable');
});

test('assertCurrentSubject describes what it found', function () {
    $this->get('/projects/42/edit')->assertOk();

    expect(fn () => $this->assertCurrentSubject('task'))
        ->toThrow(AssertionFailedError::class, 'Expected the current subject to be task:*, but it was project:42.')
        ->and(fn () => $this->assertCurrentSubject(null))
        ->toThrow(AssertionFailedError::class, 'Expected the current subject to be none, but it was project:42.');
});

test('wavykit:subjects lists kinds, and wavykit:pages shows what pages present', function () {
    $this->artisan('wavykit:subjects')
        ->expectsOutputToContain('project  WavyKit\Tests\Fixtures\Project · page projects.show · presented by projects.show')
        ->expectsOutputToContain('task  WavyKit\Tests\Fixtures\Task · page tasks.show · presented by tasks.show')
        ->assertExitCode(0);

    Artisan::call('wavykit:subjects', ['--json' => true]);
    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)[1])->toBe([
        'name' => 'task',
        'class' => Task::class,
        'page' => 'tasks.show',
        'routeParameters' => true,
        'presentedBy' => ['tasks.show'],
    ]);

    $this->artisan('wavykit:pages')->expectsOutputToContain('(dynamic) · route projects.show · presents project')->assertExitCode(0);
    $this->artisan('wavykit:check')->expectsOutputToContain('5 pages, 0 menus, 2 subjects.')->assertExitCode(0);
});

test('wavykit:check reports subject problems', function () {
    app(PageRegistry::class)->register(Page::make('orphan.subject')->presents('nothing'));

    $this->artisan('wavykit:check')
        ->expectsOutputToContain("Page 'orphan.subject' presents unknown subject 'nothing'.")
        ->assertExitCode(1);
});
