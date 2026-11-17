<?php

declare(strict_types=1);

use WavyKit\Context;
use WavyKit\CoreServiceProvider;
use WavyKit\Current;
use WavyKit\Navigation\Navigation;
use WavyKit\Pages\Pages;
use WavyKit\Subjects\Subjects;

// Laravel at the edges: builders, definitions, registries, and outputs make no
// Laravel calls; readers, the request context, providers, facades, and console
// commands hold that code.
arch('only the edges use Laravel')
    ->expect('WavyKit')
    ->not->toUse('Illuminate')
    ->ignoring([
        Context::class,
        CoreServiceProvider::class,
        Current::class,
        'WavyKit\Facades',
        'WavyKit\Console',
        Pages::class,
        Navigation::class,
        Subjects::class,
        'WavyKit\Tests',
    ]);

arch('source files declare strict types')
    ->expect('WavyKit')
    ->toUseStrictTypes()
    ->ignoring('WavyKit\Tests');
