<?php

namespace Test\ICanBoogie\View;

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\Render;

require __DIR__ . '/../vendor/autoload.php';

#
# Building the tiniest fake app for Controller
#

EventCollectionProvider::define(function () {
    static $collection;

    return $collection ??= new EventCollection();
});

function get_renderer(): Render\Renderer
{
    static $renderer;

    if ($renderer) {
        return $renderer; // @phpstan-ignore-line false-positive
    }

    $template_resolver = new Render\TemplateResolver\Basic([
        __DIR__ . '/templates',
    ]);

    $engines = new Render\EngineProvider\Immutable([
        '.phtml' => new Render\PHPEngine(),
    ]);

    return $renderer = new Render\Renderer($template_resolver, $engines);
}
