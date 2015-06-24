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

class ControllerBindingsTest extends \PHPUnit_Framework_TestCase
{
	public function test_lazy_get_view()
	{
		$controller = $this
			->getMockBuilder('ICanBoogie\View\ControllerBindingsTest\BoundController')
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		/* @var $controller \ICanBoogie\View\ControllerBindingsTest\BoundController */

		$view = $controller->view;

		$this->assertInstanceOf('ICanBoogie\View\View', $view);
		$this->assertSame($view, $controller->view);
		$this->assertObjectHasAttribute('view', $controller);
	}
}
