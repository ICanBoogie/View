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

use ICanBoogie\PropertyNotDefined;
use ICanBoogie\View\ControllerBindingsTest\BoundController;
use ICanBoogie\View\ControllerBindingsTest\BoundControllerWithLayout;
use ICanBoogie\View\ControllerBindingsTest\BoundControllerWithTemplate;
use PHPUnit\Framework\TestCase;

class ControllerBindingsTest extends TestCase
{
	public function test_view()
	{
		$controller = $this
			->getMockBuilder(BoundController::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		/* @var $controller BoundController */

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
