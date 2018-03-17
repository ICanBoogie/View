<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\View;

use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\Prototyped;
use ICanBoogie\Prototype;
use ICanBoogie\Render;
use ICanBoogie\Render\TemplateResolver;
use ICanBoogie\Render\BasicTemplateResolver;
use ICanBoogie\Routing\Controller;

require __DIR__ . '/../vendor/autoload.php';

#
# Building the tiniest fake app for Controller
#

$app = new Prototyped;

EventCollectionProvider::define(function() {

	static $collection;

	return $collection ?: $collection = new EventCollection;

});

EventCollectionProvider::provide()->attach(function(TemplateResolver\AlterEvent $event, BasicTemplateResolver $target) {

	$target->add_path(__DIR__ . '/templates');

});

#
# Configuring prototypes
#

Prototype::bind([

	Controller::class => [

		'get_app' => function () use ($app) {

			return $app;

		},

		'lazy_get_view' => function (Controller $controller) {

			$view = new View($controller, Render\get_renderer());

			new View\AlterEvent($view);

			return $view;

		}

	]

]);
