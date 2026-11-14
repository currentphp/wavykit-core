<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;
use WavyKit\Context;
use WavyKit\Navigation\MenuEntry;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;
use WavyKit\Testing\InteractsWithWavyKit;

uses(InteractsWithWavyKit::class);

beforeEach(function () {
    Route::bind('project', fn (string $id): string => $id === '42' ? 'Apollo' : 'Artemis');
    Route::middleware('bindings')->group(function () {
        Route::get('/projects', fn () => 'projects')->name('projects.index');
        Route::get('/projects/{project}', fn () => 'project')->name('projects.show');
        Route::get('/admin', fn () => 'admin')->name('admin');
        Route::get('/unregistered', fn () => 'unregistered');
    });
    $this->app['router']->aliasMiddleware('bindings', SubstituteBindings::class);
    $this->app['router']->matched(function ($event) {
        $event->request->setUserResolver(fn () => $event->request->header('X-Admin') === 'yes'
            ? new GenericUser(['id' => 'alice', 'admin' => true])
            : null);
    });

    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->children(
            Page::make('projects.show')->route('projects.show')->label(function (Context $context): string {
                $project = $context->parameter('project');

                return is_string($project) ? $project : 'Unknown';
            }),
        ),
        Page::make('admin')->route('admin')->label('Admin'),
    );
    app(MenuRegistry::class)->define('primary',
        MenuEntry::page('projects'),
        MenuEntry::page('admin')->visibleWhen(fn (Context $context): bool => $context->user instanceof GenericUser && $context->user->admin === true),
    );
});

test('assertions read the last request, including bound models and the user', function () {
    $this->get('/projects/42', ['X-Admin' => 'yes'])->assertOk();

    $this->assertCurrentPage('projects.show')
        ->assertBreadcrumbs(['Projects', 'Apollo'])
        ->assertMenuHas('primary', 'admin')
        ->assertMenuActive('primary', 'projects');
});

test('menu assertions see visibility for the request user', function () {
    $this->get('/projects')->assertOk();

    $this->assertCurrentPage('projects')
        ->assertMenuHas('primary', 'projects')
        ->assertMenuMissing('primary', 'admin');
});

test('assertCurrentPage(null) asserts that no page is active', function () {
    $this->get('/unregistered')->assertOk();

    $this->assertCurrentPage(null)->assertBreadcrumbs([]);
});

test('failures describe what was found', function (Closure $assertion, string $message) {
    $this->get('/projects/77')->assertOk();

    expect(fn () => $assertion($this))->toThrow(AssertionFailedError::class, $message);
})->with([
    'page' => [fn ($test) => $test->assertCurrentPage('admin'), "Expected the current page to be 'admin', but it was 'projects.show'."],
    'none' => [fn ($test) => $test->assertCurrentPage(null), "Expected the current page to be none, but it was 'projects.show'."],
    'breadcrumbs' => [fn ($test) => $test->assertBreadcrumbs(['Projects', 'Apollo']), 'Expected breadcrumbs [Projects > Apollo], but they were [Projects > Artemis].'],
    'has' => [fn ($test) => $test->assertMenuHas('primary', 'admin'), "Expected menu 'primary' to show page 'admin'.\nThe menu was:\n  - Projects (projects) [active]"],
    'missing' => [fn ($test) => $test->assertMenuMissing('primary', 'projects'), "Expected menu 'primary' not to show page 'projects'."],
    'active' => [fn ($test) => $test->assertMenuActive('primary', 'admin'), "Expected page 'admin' to be active in menu 'primary', but it was not in the menu."],
]);
