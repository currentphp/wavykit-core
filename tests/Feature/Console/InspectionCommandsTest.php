<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use WavyKit\Context;
use WavyKit\Navigation\MenuEntry;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;

/** @return array<mixed> */
function artisanJson(string $command): array
{
    expect(Artisan::call($command))->toBe(0);

    return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
}

function registerInspectionFixture(): void
{
    Route::get('/projects', fn () => 'projects')->name('projects.index');
    Route::get('/projects/{project}', fn () => 'project')->name('projects.show');
    Route::get('/settings', fn () => 'settings')->name('settings');

    app(PageRegistry::class)->register(
        Page::make('projects')->route('projects.index')->label('Projects')->icon('folder')->children(
            Page::make('projects.show')->route('projects.show')
                ->label(fn (Context $context): string => throw new LogicException('Commands must not run callbacks.')),
        ),
        Page::make('settings')->route('settings')->label('Settings')->order(10),
        Page::make('help')->label('Help'),
    );
    app(MenuRegistry::class)->derive('primary', 'projects', 'settings')->except('projects.show');
    app(MenuRegistry::class)->define('account',
        MenuEntry::group('you')->label('You')->children(
            MenuEntry::page('settings')->visibleWhen(fn (Context $context): bool => $context->user !== null),
        ),
    );
}

test('wavykit:pages prints the page tree without running callbacks', function () {
    registerInspectionFixture();

    $this->artisan('wavykit:pages')
        ->expectsOutputToContain('Projects · route projects.index · icon folder')
        ->expectsOutputToContain('(dynamic) · route projects.show')
        ->expectsOutputToContain('Help · no route')
        ->assertExitCode(0);
});

test('wavykit:pages --json prints the page tree as data', function () {
    registerInspectionFixture();

    $pages = artisanJson('wavykit:pages --json');

    expect(array_column($pages, 'id'))->toBe(['projects', 'help', 'settings'])
        ->and($pages[0])->toMatchArray([
            'label' => 'Projects',
            'dynamicLabel' => false,
            'route' => 'projects.index',
            'routeParameters' => false,
            'icon' => 'folder',
        ])
        ->and($pages[0]['children'][0])->toMatchArray([
            'id' => 'projects.show',
            'label' => null,
            'dynamicLabel' => true,
            'children' => [],
        ]);
});

test('wavykit:menus prints derived and defined menus', function () {
    registerInspectionFixture();

    $this->artisan('wavykit:menus')
        ->expectsOutputToContain('primary (derived from projects, settings; except projects.show)')
        ->expectsOutputToContain('account (defined)')
        ->expectsOutputToContain('You · group')
        ->expectsOutputToContain('Settings · visible when')
        ->assertExitCode(0);
});

test('wavykit:menus --json describes one menu', function () {
    registerInspectionFixture();

    $menus = artisanJson('wavykit:menus primary --json');

    expect($menus)->toHaveCount(1)
        ->and($menus[0])->toMatchArray([
            'name' => 'primary',
            'kind' => 'derived',
            'roots' => ['projects', 'settings'],
            'except' => ['projects.show'],
        ])
        ->and(array_column($menus[0]['entries'], 'id'))->toBe(['projects', 'settings'])
        ->and($menus[0]['entries'][0]['children'])->toBe([]);

    $account = artisanJson('wavykit:menus account --json')[0];
    expect($account['kind'])->toBe('defined')
        ->and($account['entries'][0])->toMatchArray(['kind' => 'group', 'id' => 'you', 'label' => 'You', 'visibility' => false])
        ->and($account['entries'][0]['children'][0])->toMatchArray(['kind' => 'page', 'id' => 'settings', 'label' => 'Settings', 'visibility' => true]);
});

test('wavykit:menus rejects unknown menu names', function () {
    registerInspectionFixture();

    $this->artisan('wavykit:menus missing')
        ->expectsOutputToContain("Unknown menu 'missing'.")
        ->assertExitCode(1);
});

test('wavykit:check passes valid definitions', function () {
    registerInspectionFixture();

    $this->artisan('wavykit:check')
        ->expectsOutputToContain('WavyKit definitions are valid: 4 pages, 2 menus.')
        ->assertExitCode(0);
});

test('wavykit:check reports every unknown route and a menu problem', function () {
    app(PageRegistry::class)->register(
        Page::make('first')->route('missing.first'),
        Page::make('second')->route('missing.second'),
    );
    app(MenuRegistry::class)->derive('primary', 'nowhere');

    $this->artisan('wavykit:check')
        ->expectsOutputToContain("Page 'first' references unknown route 'missing.first'.")
        ->expectsOutputToContain("Page 'second' references unknown route 'missing.second'.")
        ->expectsOutputToContain("Menu 'primary' references unknown page 'nowhere'.")
        ->assertExitCode(1);
});

test('listing commands fail with the same problems', function (string $command) {
    app(PageRegistry::class)->register(Page::make('orphan')->parent('missing'));

    $this->artisan($command)
        ->expectsOutputToContain("Page 'orphan' references unknown parent 'missing'.")
        ->assertExitCode(1);
})->with(['wavykit:pages', 'wavykit:menus', 'wavykit:check']);

test('empty registries are reported plainly', function () {
    $this->artisan('wavykit:pages')->expectsOutputToContain('No pages are registered.')->assertExitCode(0);
    $this->artisan('wavykit:menus')->expectsOutputToContain('No menus are registered.')->assertExitCode(0);
});
