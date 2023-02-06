<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\View;

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\Render;
use ICanBoogie\Render\BasicTemplateResolver;

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
        return $renderer;
    }

    $template_resolver = new BasicTemplateResolver([
        __DIR__ . '/templates'
    ]);

    $engines = new Render\EngineProvider\Immutable([
        '.phtml' => new Render\PHPEngine()
    ]);

    return $renderer = new Render\Renderer($template_resolver, $engines);
}
