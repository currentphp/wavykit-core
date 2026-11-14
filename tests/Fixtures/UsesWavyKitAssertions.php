<?php

declare(strict_types=1);

namespace WavyKit\Tests\Fixtures;

use WavyKit\Testing\InteractsWithWavyKit;
use WavyKit\Tests\TestCase;

/** Lets PHPStan analyse the assertions trait, which no class in src uses. */
abstract class UsesWavyKitAssertions extends TestCase
{
    use InteractsWithWavyKit;
}
