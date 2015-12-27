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
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Controller;
use ICanBoogie\Routing\Route;

class ViewTest extends \PHPUnit_Framework_TestCase
{
	const FIXTURE_CONTENT = "TESTING";

	static private function generate_bytes($length = 2048)
	{
		return openssl_random_pseudo_bytes($length);
	}

	/**
	 * @var Controller|ControllerBindings
	 */
	private $controller;

	/**
	 * @var EventCollection
	 */
	private $events;

	public function setUp()
	{
		$this->controller = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->events = EventCollectionProvider::provide();
	}

	public function test_get_controller()
	{
		$controller = $this->controller;
		$view = new View($controller);
		$this->assertSame($controller, $view->controller);
	}

	public function test_get_variables()
	{
		$controller = $this->controller;
		$content = self::generate_bytes();
		$v1 = self::generate_bytes();
		$v2 = self::generate_bytes();

		$view = new View($controller);
		$view->content = $content;
		$view['v1'] = $v1;
		$view['v2'] = $v2;

		$this->assertSame($content, $view->content);
		$this->assertSame($content, $view['content']);
		$this->assertEquals([ 'content' => $content, 'v1' => $v1, 'v2' => $v2, 'view' => $view ], $view->variables);
	}

	/**
	 * @dataProvider provide_test_get_template
	 *
	 * @param $controller
	 * @param $expected
	 */
	public function test_get_template($controller, $expected)
	{
		$view = new View($controller);

		$this->assertSame($expected, $view->template);
	}

	public function provide_test_get_template()
	{
		$t1 = 'template' . uniqid();

		$r1 = new Route('/', [ 'template' => $t1 ]);

		$c1 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c1->expects($this->once())
			->method('get_route')
			->willReturn($r1);

		#

		$t2 = 'template' . uniqid();

		$c2 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c2->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		/* @var $c2 Controller|ControllerBindings */

		$c2->template = $t2;

		#

		$c3_name = uniqid();
		$c3_action = uniqid();

		$t3 = "$c3_name/$c3_action";

		$c3 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route', 'get_name', 'get_action' ])
			->getMockForAbstractClass();

		$c3->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		$c3->expects($this->once())
			->method('get_name')
			->willReturn($c3_name);

		$c3->expects($this->once())
			->method('get_action')
			->willReturn($c3_action);

		#

		$t4 = null;

		$c4 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c4->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		#

		return [

			[ $c1, $t1 ],
			[ $c2, $t2 ],
			[ $c3, $t3 ],
			[ $c4, $t4 ]

		];
	}

	/**
	 * @dataProvider provide_test_get_layout
	 *
	 * @param $view
	 * @param $expected
	 */
	public function test_get_layout($view, $expected)
	{
		$this->assertSame($expected, $view->layout);
	}

	public function provide_test_get_layout()
	{
		#
		# $controller->route->layout
		#

		$t1 = 'layout' . uniqid();

		$r1 = new Route('/', [ 'layout' => $t1 ]);

		$c1 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c1->expects($this->once())
			->method('get_route')
			->willReturn($r1);

		/* @var $c1 Controller */

		$v1 = new View($c1);

		#
		# $controller->layout
		#

		$t2 = 'layout' . uniqid();

		$c2 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c2->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		/* @var $c2 Controller|ControllerBindings */

		$c2->layout = $t2;

		$v2 = new View($c2);

		#
		# $controller->route->id
		#

		$t3 = 'admin';

		$c3_route = $this
			->getMockBuilder(Route::class)
			->disableOriginalConstructor()
			->setMethods([ '__get' ])
			->getMock();
		$c3_route->expects($this->any())
			->method('__get')
			->willReturnCallback(function($property) {

				if ($property == 'layout') throw new PropertyNotDefined($property);
				elseif ($property == 'id') return 'admin:posts/index';

			});

		$c3 = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c3->expects($this->any())
			->method('get_route')
			->willReturn($c3_route);

		/* @var $c3 Controller */

		$v3 = new View($c3);

		#
		# 'page'
		#

		return [

			[ $v1, $t1 ],
			[ $v2, $t2 ],
			[ $v3, $t3 ],
			$this->provide_test_get_layout_case3(),
			$this->provide_test_get_layout_case4()

		];
	}

	/*
	 * get_layout: home
	 */
	private function provide_test_get_layout_case3()
	{
		$expected = 'home';

		$route = $this
			->getMockBuilder(Route::class)
			->disableOriginalConstructor()
			->setMethods([ '__get' ])
			->getMock();
		$route->expects($this->any())
			->method('__get')
			->willReturnCallback(function($property) {

				switch ($property)
				{
					case 'layout':
						throw new PropertyNotDefined($property);
					case 'id':
						return 'posts/index';
					case 'pattern':
						return '/';
				}

			});

		$controller = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder(View::class)
			->setConstructorArgs([ $controller ])
			->setMethods([ 'resolve_template' ])
			->getMock();

		$view
			->expects($this->once())
			->method('resolve_template')
			->with('home', View::TEMPLATE_PREFIX_LAYOUT)
			->willReturn(true);

		return [ $view, $expected ];
	}

	/*
	 * get_layout: page
	 */
	private function provide_test_get_layout_case4()
	{
		$route = $this
			->getMockBuilder(Route::class)
			->disableOriginalConstructor()
			->setMethods([ '__get' ])
			->getMock();
		$route->expects($this->any())
			->method('__get')
			->willReturnCallback(function($property) {

				switch ($property)
				{
					case 'layout':
						throw new PropertyNotDefined($property);
					case 'id':
						return 'posts/index';
					case 'pattern':
						return '/';
				}

			});

		$controller = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder(View::class)
			->setConstructorArgs([ $controller ])
			->setMethods([ 'resolve_template' ])
			->getMock();

		$view
			->expects($this->exactly(2))
			->method('resolve_template')
			->willReturnCallback(function($name, $type) {

				if ($name == 'home') return false;
				elseif ($name == 'page') return true;

			});

		return [ $view, 'page' ];
	}

	/*
	 * get_layout: default
	 */
	private function provide_test_get_layout_case5()
	{
		$route = $this
			->getMockBuilder(Route::class)
			->disableOriginalConstructor()
			->setMethods([ '__get' ])
			->getMock();
		$route->expects($this->any())
			->method('__get')
			->willReturnCallback(function($property) {

				switch ($property)
				{
					case 'layout':
						throw new PropertyNotDefined($property);
					case 'id':
						return 'posts/index';
					case 'pattern':
						return '/';
				}

			});

		$controller = $this
			->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder(View::class)
			->setConstructorArgs([ $controller ])
			->setMethods([ 'resolve_template' ])
			->getMock();

		$view
			->expects($this->exactly(2))
			->method('resolve_template')
			->willReturn(false);

		return [ $view, 'default' ];
	}

	public function test_array_interface()
	{
		$view = new View($this->controller);
		$this->assertFalse(isset($view['content']));
		$expected = $this->generate_bytes();
		$view['content'] = $expected;
		$this->assertTrue(isset($view['content']));
		$this->assertEquals($expected, $view['content']);
		unset($view['content']);
		$this->assertFalse(isset($view['content']));
	}

	/**
	 * @expectedException \ICanBoogie\OffsetNotDefined
	 */
	public function test_should_throw_exception_on_undefined_offset()
	{
		$view = new View($this->controller);
		$view[uniqid()];
	}

	public function test_view_getter()
	{
		$controller = $this->controller;
		$this->assertInstanceOf(View::class, $controller->view);
	}

	public function test_should_throw_exception_if_template_doesnot_exists()
	{
		$template = 'undefined' . uniqid();

		$view = $this
			->getMockBuilder(View::class)
			->setConstructorArgs([ $this->controller ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();
		$view
			->expects($this->once())
			->method('get_template')
			->willReturn($template);

		/* @var $view View */

		try
		{
			$view->render();
			$this->fail("Expected TemplateNotFound");
		}
		catch (TemplateNotFound $e)
		{
			$this->assertContains("no template matching", $e->getMessage());
			$this->assertContains($template, $e->getMessage());
		}
	}

	public function test_should_throw_exception_if_layout_doesnot_exists()
	{
		$template = 'undefined' . uniqid();

		$view = $this
			->getMockBuilder(View::class)
			->setConstructorArgs([ $this->controller ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();
		$view
			->expects($this->once())
			->method('get_layout')
			->willReturn($template);

		/* @var $view View */

		try
		{
			$view->render();
			$this->fail("Expected TemplateNotFound");
		}
		catch (TemplateNotFound $e)
		{
			$this->assertContains("no layout matching", $e->getMessage());
			$this->assertContains($template, $e->getMessage());
		}
	}

	public function test_render_with_decorator()
	{
		$view = $this
			->getMockBuilder(View::class)
			->setConstructorArgs([ $this->controller ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();
		$view
			->expects($this->once())
			->method('get_template')
			->willReturn('decorated');

		/* @var $view View */

		$content = 'MYCONTENT' . uniqid();
		$v1 = 'V1' . uniqid();
		$v2 = 'V2' . uniqid();
		$view->content = $content;
		$view['v1'] = $v1;
		$view['v2'] = $v2;
		$view->decorate_with('decorator');
		$view_class = get_class($view);

		$expected = <<<EOT
<DECORATED>---
CONTENT: $content|
V1: $v1|
V2: $v2|
VIEW: $view_class|
===
</DECORATED>

EOT;

		$this->assertEquals($expected, $view->render());
	}

	public function test_view_render()
	{
		$request = Request::from("/");
		$request->context->route = new Route('/', [ 'layout' => null ]);

		$controller = $this
			->getMockBuilder(Controller::class)
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function() use ($controller) {
				/* @var $controller Controller|ControllerBindings */
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		/* @var $controller Controller */
		$response = $controller($request);
		$this->assertEquals(self::FIXTURE_CONTENT, $response);
	}

	public function test_view_render_with_default_layout()
	{
		$request = Request::from("/");

		$controller = $this
			->getMockBuilder(Controller::class)
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function() use ($controller) {
				/* @var $controller Controller|ControllerBindings */
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		/* @var $controller Controller */

		$request->context->route = new Route('/', []);

		$response = $controller($request);
		$this->assertEquals(<<<EOT
<default>TESTING</default>

EOT
		, $response);
	}

	public function test_view_render_with_custom_layout()
	{
		$request = Request::from("/");
		$request->context->route = new Route('/', [ 'layout' => 'custom' ]);

		$controller = $this
			->getMockBuilder(Controller::class)
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(\Closure::bind(function() {

				/* @var $this Controller|ControllerBindings */
				$this->view->content = ViewTest::FIXTURE_CONTENT;

			}, $controller));

		/* @var $controller Controller */
		$response = $controller($request);
		$this->assertEquals(<<<EOT
<custom>TESTING</custom>

EOT
		, $response);
	}

	public function test_controller_with_json_response()
	{
		$request = Request::from("/");
		$request->context->route = new Route('/', [ ]);

		$controller = $this
			->getMockBuilder(Controller::class)
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(\Closure::bind(function() {

				/* @var $this Controller|ControllerBindings */
				$this->view->content = [ 1 => "one", 2 => "two" ];
				$this->view->template = "json";
				$this->view->layout = null;

				$this->response->content_type = "application/json";

			}, $controller));

		/* @var $controller Controller */
		$response = $controller($request);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals("application/json", $response->content_type);
		$this->assertEquals('{"1":"one","2":"two"}', $response->body);
	}

	public function test_on_action_should_preserve_result()
	{
		$view = $this
			->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->setMethods([ 'render' ])
			->getMock();
		$view
			->expects($this->never())
			->method('render');

		$result = uniqid();

		$event = $this
			->getMockBuilder(Controller\ActionEvent::class)
			->disableOriginalConstructor()
			->getMock();

		/* @var $event Controller\ActionEvent */

		$event->result = $result;

		$on_action = new \ReflectionMethod($view, 'on_action');
		$on_action->setAccessible(true);
		$on_action->invoke($view, $event);

		$this->assertSame($result, $event->result);
	}

	public function test_on_action_should_preserve_before_render_event_result()
	{
		$expected_result = uniqid();
		$controller = $this->controller;
		$view = new View($controller);

		$this->events->attach_to($view, function(View\BeforeRenderEvent $event, View $target) use ($expected_result) {

			$event->result = $expected_result;

		});

		$result = null;

		new Controller\ActionEvent($controller, $result);

		$this->assertEquals($expected_result, $result);
	}

	public function test_should_remove_this_during_json_serializ_if_view()
	{
		$view = new View($this->controller);
		$view->template = $template = uniqid();
		$view->layout = $layout = uniqid();
		$view['this'] = $view;
		$view['var'] = $var = uniqid();

		$array = $view->jsonSerialize();
		$this->assertEquals($template, $array['template']);
		$this->assertEquals($layout, $array['layout']);
		$this->assertEquals($var, $array['variables']['var']);
		$this->assertArrayNotHasKey('this', $array['variables']);
	}

	public function test_should_remove_preserve_this_during_json_serializ_if_not_view()
	{
		$view = new View($this->controller);
		$view->template = $template = uniqid();
		$view->layout = $layout = uniqid();
		$view['this'] = $that = (object) [ 'property' => uniqid() ];
		$view['var'] = $var = uniqid();

		$array = $view->jsonSerialize();
		$this->assertEquals($template, $array['template']);
		$this->assertEquals($layout, $array['layout']);
		$this->assertEquals($var, $array['variables']['var']);
		$this->assertArrayHasKey('this', $array['variables']);
		$this->assertEquals($that, $array['variables']['this']);
	}
}
