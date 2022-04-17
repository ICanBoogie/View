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

use ICanBoogie\HTTP\Request;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\View\View;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\View\ControllerBindingsTest\BoundController;
use Test\ICanBoogie\View\ControllerBindingsTest\BoundControllerWithLayout;
use Test\ICanBoogie\View\ControllerBindingsTest\BoundControllerWithTemplate;

class ControllerBindingsTest extends TestCase
{
	public function test_view()
	{
		$controller = new class () extends BoundController {
			protected function action(Request $request): mixed
			{
				return "MADONNA";
			}
		};

		$view = $controller->view;

		$this->assertInstanceOf(View::class, $view);
		$this->assertSame($view, $controller->view);
		$this->assertObjectHasAttribute('view', $controller);
	}

	public function test_template()
	{
		$controller = $this
			->getMockBuilder(BoundController::class)
			->getMockForAbstractClass();

		/* @var $controller BoundController */

		$this->expectException(PropertyNotDefined::class);
		$controller->template;
	}

	public function test_layout()
	{
		$controller = $this
			->getMockBuilder(BoundController::class)
			->getMockForAbstractClass();

		/* @var $controller BoundController */

		$this->expectException(PropertyNotDefined::class);
		$controller->layout;
	}

	public function test_template_when_defined()
	{
		$controller = $this
			->getMockBuilder(BoundControllerWithTemplate::class)
			->getMockForAbstractClass();

		/* @var $controller BoundControllerWithTemplate */

		$this->assertEquals('my-template', $controller->template);
	}

	public function test_layout_when_defined()
	{
		$controller = $this
			->getMockBuilder(BoundControllerWithLayout::class)
			->getMockForAbstractClass();

		/* @var $controller BoundControllerWithLayout */

		$this->assertEquals('my-layout', $controller->layout);
	}
}
