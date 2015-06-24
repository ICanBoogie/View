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

use ICanBoogie\Events;
use ICanBoogie\Object;
use ICanBoogie\Prototype;
use ICanBoogie\Render;
use ICanBoogie\Render\TemplateResolver;
use ICanBoogie\Render\BasicTemplateResolver;
use ICanBoogie\Routing\Controller;

$autoload = require __DIR__ . '/../vendor/autoload.php';
$autoload->addPsr4('ICanBoogie\\View\\ControllerBindingsTest\\', __DIR__ . '/ControllerBindingsTest/');

#
# Building the tiniest fake app for Controller
#

$app = new Object;

Events::get()->attach(function(TemplateResolver\AlterEvent $event, BasicTemplateResolver $target) {

	$target->add_path(__DIR__ . '/templates');

});

#
# Configuring prototypes
#

Prototype::configure([

	'ICanBoogie\Routing\Controller' => [

		'get_app' => function() use ($app) {

			return $app;

		},

		'lazy_get_view' => function(Controller $controller) {

			$view = new View($controller);

			new View\AlterEvent($view);

			return $view;

		}

	],

	'ICanBoogie\View\View' => [

		'lazy_get_engines' => function(){

			return Render\get_engines();

		},

		'lazy_get_template_resolver' => function() {

			return clone Render\get_template_resolver();

		}
	]

]);
