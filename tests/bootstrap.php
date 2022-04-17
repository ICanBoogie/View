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
use ICanBoogie\Prototype;
use ICanBoogie\Prototyped;
use ICanBoogie\Render;
use ICanBoogie\Render\BasicTemplateResolver;
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\View\View;

require __DIR__ . '/../vendor/autoload.php';

#
# Building the tiniest fake app for Controller
#

$app = new Prototyped();

EventCollectionProvider::define(function () {
	static $collection;

	return $collection ??= new EventCollection;
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
		'.php' => new Render\PHPEngine()
	]);

	return $renderer = new Render\Renderer($template_resolver, $engines);
}

#
# Configuring prototypes
#

Prototype::bind([

	ControllerAbstract::class => [
		'get_app' => function () use ($app) {
			return $app;
		},

		'lazy_get_view' => function (ControllerAbstract $controller) {
			$view = new View($controller, get_renderer());

			new View\AlterEvent($view);

			return $view;
		}
	]

]);
