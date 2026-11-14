<?php

declare(strict_types=1);

namespace WavyKit;

use Illuminate\Support\ServiceProvider;
use WavyKit\Console\CheckCommand;
use WavyKit\Console\MenusCommand;
use WavyKit\Console\PagesCommand;
use WavyKit\Navigation\MenuRegistry;
use WavyKit\Navigation\Navigation;
use WavyKit\Pages\PageRegistry;
use WavyKit\Pages\Pages;

/** Shares declarations across requests while scoping resolvers to the application request/job lifecycle. */
final class CoreServiceProvider extends ServiceProvider
{
    /** @internal Called by Laravel; the bindings and their lifetimes are implementation details. */
    public function register(): void
    {
        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->scoped(Pages::class);
        $this->app->scoped(Navigation::class);
        // Stateless, so the facade's cached instance never holds request state.
        $this->app->singleton(Current::class);
    }

    /** @internal Called by Laravel; registers the inspection commands in the console. */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PagesCommand::class, MenusCommand::class, CheckCommand::class]);
        }
    }
}
