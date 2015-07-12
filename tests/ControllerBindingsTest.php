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

use ICanBoogie\View\ControllerBindingsTest\BoundController;
use ICanBoogie\View\ControllerBindingsTest\BoundControllerWithLayout;
use ICanBoogie\View\ControllerBindingsTest\BoundControllerWithTemplate;

class ControllerBindingsTest extends \PHPUnit_Framework_TestCase
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

	/**
	 * @expectedException \ICanBoogie\PropertyNotDefined
	 */
	public function test_template()
	{
		$controller = $this
			->getMockBuilder(BoundController::class)
			->getMockForAbstractClass();

		/* @var $controller BoundController */

		$controller->template;
	}

	/**
	 * @expectedException \ICanBoogie\PropertyNotDefined
	 */
	public function test_layout()
	{
		$controller = $this
			->getMockBuilder(BoundController::class)
			->getMockForAbstractClass();

		/* @var $controller BoundController */

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
