<?php

declare(strict_types=1);

/**
 * Every public class, method, property, and enum case in src that is not marked
 *
 * @internal. Changing this list changes the supported API: update the
 * CHANGELOG, and flag removals and signature changes as breaking.
 */
const SUPPORTED_API = [
    'WavyKit\Context',
    'WavyKit\Context::$page',
    'WavyKit\Context::$user',
    'WavyKit\Context::parameter()',
    'WavyKit\Context::parameters()',
    'WavyKit\CoreServiceProvider',
    'WavyKit\Current',
    'WavyKit\Current::breadcrumbs()',
    'WavyKit\Current::context()',
    'WavyKit\Current::is()',
    'WavyKit\Current::menu()',
    'WavyKit\Current::page()',
    'WavyKit\Current::pageId()',
    'WavyKit\Current::subject()',
    'WavyKit\Current::user()',
    'WavyKit\Exceptions\InvalidDefinition',
    'WavyKit\Exceptions\ResolutionFailed',
    'WavyKit\Exceptions\WavyKitException',
    'WavyKit\Facades\Current',
    'WavyKit\Navigation\Breadcrumb',
    'WavyKit\Navigation\Breadcrumb::$current',
    'WavyKit\Navigation\Breadcrumb::$icon',
    'WavyKit\Navigation\Breadcrumb::$label',
    'WavyKit\Navigation\Breadcrumb::$pageId',
    'WavyKit\Navigation\Breadcrumb::$url',
    'WavyKit\Navigation\DefinedMenu',
    'WavyKit\Navigation\DerivedMenu',
    'WavyKit\Navigation\DerivedMenu::except()',
    'WavyKit\Navigation\MenuEntry',
    'WavyKit\Navigation\MenuEntry::$id',
    'WavyKit\Navigation\MenuEntry::children()',
    'WavyKit\Navigation\MenuEntry::group()',
    'WavyKit\Navigation\MenuEntry::icon()',
    'WavyKit\Navigation\MenuEntry::label()',
    'WavyKit\Navigation\MenuEntry::page()',
    'WavyKit\Navigation\MenuEntry::visibleWhen()',
    'WavyKit\Navigation\MenuItem',
    'WavyKit\Navigation\MenuItem::$active',
    'WavyKit\Navigation\MenuItem::$children',
    'WavyKit\Navigation\MenuItem::$current',
    'WavyKit\Navigation\MenuItem::$icon',
    'WavyKit\Navigation\MenuItem::$kind',
    'WavyKit\Navigation\MenuItem::$label',
    'WavyKit\Navigation\MenuItem::$pageId',
    'WavyKit\Navigation\MenuItem::$url',
    'WavyKit\Navigation\MenuItemKind',
    'WavyKit\Navigation\MenuItemKind::Group',
    'WavyKit\Navigation\MenuItemKind::Page',
    'WavyKit\Navigation\MenuRegistry',
    'WavyKit\Navigation\MenuRegistry::define()',
    'WavyKit\Navigation\MenuRegistry::derive()',
    'WavyKit\Navigation\Navigation',
    'WavyKit\Navigation\Navigation::breadcrumbs()',
    'WavyKit\Navigation\Navigation::menu()',
    'WavyKit\Pages\Page',
    'WavyKit\Pages\Page::$id',
    'WavyKit\Pages\Page::children()',
    'WavyKit\Pages\Page::icon()',
    'WavyKit\Pages\Page::label()',
    'WavyKit\Pages\Page::make()',
    'WavyKit\Pages\Page::order()',
    'WavyKit\Pages\Page::parent()',
    'WavyKit\Pages\Page::presents()',
    'WavyKit\Pages\Page::route()',
    'WavyKit\Pages\Page::routeParameters()',
    'WavyKit\Pages\PageDefinition',
    'WavyKit\Pages\PageDefinition::$icon',
    'WavyKit\Pages\PageDefinition::$id',
    'WavyKit\Pages\PageDefinition::$label',
    'WavyKit\Pages\PageDefinition::$labelled',
    'WavyKit\Pages\PageDefinition::$order',
    'WavyKit\Pages\PageDefinition::$parentId',
    'WavyKit\Pages\PageDefinition::$presents',
    'WavyKit\Pages\PageDefinition::$presentsParameter',
    'WavyKit\Pages\PageDefinition::$routeName',
    'WavyKit\Pages\PageDefinition::$routeParameters',
    'WavyKit\Pages\PageRegistry',
    'WavyKit\Pages\PageRegistry::all()',
    'WavyKit\Pages\PageRegistry::page()',
    'WavyKit\Pages\PageRegistry::register()',
    'WavyKit\Pages\Pages',
    'WavyKit\Pages\Pages::activeTrail()',
    'WavyKit\Pages\Pages::context()',
    'WavyKit\Pages\Pages::current()',
    'WavyKit\Pages\Pages::label()',
    'WavyKit\Pages\Pages::url()',
    'WavyKit\Subjects\Subject',
    'WavyKit\Subjects\Subject::$name',
    'WavyKit\Subjects\Subject::for()',
    'WavyKit\Subjects\Subject::label()',
    'WavyKit\Subjects\Subject::make()',
    'WavyKit\Subjects\Subject::page()',
    'WavyKit\Subjects\SubjectDefinition',
    'WavyKit\Subjects\SubjectDefinition::$class',
    'WavyKit\Subjects\SubjectDefinition::$label',
    'WavyKit\Subjects\SubjectDefinition::$name',
    'WavyKit\Subjects\SubjectDefinition::$pageId',
    'WavyKit\Subjects\SubjectDefinition::$routeParameters',
    'WavyKit\Subjects\SubjectItem',
    'WavyKit\Subjects\SubjectItem::$id',
    'WavyKit\Subjects\SubjectItem::$kind',
    'WavyKit\Subjects\SubjectItem::$label',
    'WavyKit\Subjects\SubjectItem::$url',
    'WavyKit\Subjects\SubjectItem::key()',
    'WavyKit\Subjects\SubjectRegistry',
    'WavyKit\Subjects\SubjectRegistry::all()',
    'WavyKit\Subjects\SubjectRegistry::kind()',
    'WavyKit\Subjects\SubjectRegistry::register()',
    'WavyKit\Subjects\Subjects',
    'WavyKit\Subjects\Subjects::current()',
    'WavyKit\Subjects\Subjects::of()',
    'WavyKit\Testing\InteractsWithWavyKit',
    'WavyKit\Testing\InteractsWithWavyKit::assertBreadcrumbs()',
    'WavyKit\Testing\InteractsWithWavyKit::assertCurrentPage()',
    'WavyKit\Testing\InteractsWithWavyKit::assertCurrentSubject()',
    'WavyKit\Testing\InteractsWithWavyKit::assertMenuActive()',
    'WavyKit\Testing\InteractsWithWavyKit::assertMenuHas()',
    'WavyKit\Testing\InteractsWithWavyKit::assertMenuMissing()',
];

/** @return list<string> */
function supportedApi(): array
{
    $root = dirname(__DIR__, 2).'/src';
    $isInternal = fn (Reflector $element): bool => str_contains((string) $element->getDocComment(), '@internal');
    $symbols = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $class = 'WavyKit\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($root) + 1));
        $reflection = new ReflectionClass($class);
        if ($isInternal($reflection)) {
            continue;
        }
        $symbols[] = $class;

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $class && ! $method->isInternal() && ! $isInternal($method)) {
                $symbols[] = "{$class}::{$method->getName()}()";
            }
        }
        if ($reflection->isEnum()) {
            foreach (array_keys($reflection->getConstants()) as $case) {
                $symbols[] = "{$class}::{$case}";
            }

            continue;
        }
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() === $class && ! $isInternal($property)) {
                $symbols[] = "{$class}::\${$property->getName()}";
            }
        }
    }
    sort($symbols);

    return $symbols;
}

test('the supported API changes only deliberately', function () {
    expect(supportedApi())->toBe(SUPPORTED_API);
});

test('every supported method is documented', function () {
    $undocumented = array_filter(
        SUPPORTED_API,
        function (string $symbol): bool {
            if (! str_ends_with($symbol, '()')) {
                return false;
            }
            [$class, $method] = explode('::', substr($symbol, 0, -2));

            return (new ReflectionMethod($class, $method))->getDocComment() === false;
        },
    );

    expect(array_values($undocumented))->toBe([]);
});
