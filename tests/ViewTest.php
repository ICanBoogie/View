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

use ICanBoogie\HTTP\Request;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Render\BasicTemplateResolver;
use ICanBoogie\Routing\Route;

class ViewTest extends \PHPUnit_Framework_TestCase
{
	const FIXTURE_CONTENT = "TESTING";

	static private function generate_bytes($length = 2048)
	{
		return openssl_random_pseudo_bytes($length);
	}

	private $controller_stub;

	private $routes;

	public function setUp()
	{
		$this->controller_stub = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->routes = $this
			->getMockBuilder('ICanBoogie\Routing\Routes')
			->disableOriginalConstructor()
			->getMock();
	}

	public function test_get_controller()
	{
		$controller = $this->controller_stub;
		$view = new View($controller);
		$this->assertSame($controller, $view->controller);
	}

	public function test_get_variables()
	{
		$controller = $this->controller_stub;
		$content = self::generate_bytes();
		$v1 = self::generate_bytes();
		$v2 = self::generate_bytes();

		$view = new View($controller);
		$view->content = $content;
		$view['v1'] = $v1;
		$view['v2'] = $v2;

		$this->assertSame($content, $view->content);
		$this->assertSame($content, $view['content']);
		$this->assertEquals([ 'content' => $content, 'v1' => $v1, 'v2' => $v2 ], $view->variables);
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
		$routes = $this
			->getMockBuilder('ICanBoogie\Routing\Routes')
			->disableOriginalConstructor()
			->getMock();

		/* @var $routes \ICanBoogie\Routing\Routes */

		$t1 = 'template' . uniqid();

		$r1 = new Route($routes, '/', [ 'template' => $t1 ]);

		$c1 = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c1->expects($this->once())
			->method('get_route')
			->willReturn($r1);

		#

		$t2 = 'template' . uniqid();

		$c2 = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c2->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		$c2->template = $t2;

		#

		$c3_name = uniqid();
		$c3_action = uniqid();

		$t3 = "$c3_name/$c3_action";

		$c3 = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
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
			->getMockBuilder('ICanBoogie\Routing\Controller')
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
		$routes = $this
			->getMockBuilder('ICanBoogie\Routing\Routes')
			->disableOriginalConstructor()
			->getMock();

		/* @var $routes \ICanBoogie\Routing\Routes */

		#
		# $controller->route->layout
		#

		$t1 = 'layout' . uniqid();

		$r1 = new Route($routes, '/', [ 'layout' => $t1 ]);

		$c1 = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c1->expects($this->once())
			->method('get_route')
			->willReturn($r1);

		/* @var $c1 \ICanBoogie\Routing\Controller */

		$v1 = new View($c1);

		#
		# $controller->layout
		#

		$t2 = 'layout' . uniqid();

		$c2 = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c2->expects($this->once())
			->method('get_route')
			->willThrowException(new PropertyNotDefined('route'));

		/* @var $c2 \ICanBoogie\Routing\Controller */

		$c2->layout = $t2;

		$v2 = new View($c2);

		#
		# $controller->route->id
		#

		$t3 = 'admin';

		$c3_route = $this
			->getMockBuilder('ICanBoogie\Routing\Route')
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
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$c3->expects($this->any())
			->method('get_route')
			->willReturn($c3_route);

		/* @var $c3 \ICanBoogie\Routing\Controller */

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
			->getMockBuilder('ICanBoogie\Routing\Route')
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
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder('ICanBoogie\View\View')
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
			->getMockBuilder('ICanBoogie\Routing\Route')
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
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder('ICanBoogie\View\View')
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
			->getMockBuilder('ICanBoogie\Routing\Route')
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
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->disableOriginalConstructor()
			->setMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$view = $this->getMockBuilder('ICanBoogie\View\View')
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
		$view = new View($this->controller_stub);
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
		$view = new View($this->controller_stub);
		$view[uniqid()];
	}

	public function test_add_path()
	{
		$path = __DIR__;
		$template_resolver = new BasicTemplateResolver;
		$view = new View($this->controller_stub);
		$view->template_resolver = $template_resolver;
		$view->add_path($path);

		$this->assertEquals([ __DIR__ . DIRECTORY_SEPARATOR ], $template_resolver->get_paths());
	}

	public function test_view_getter()
	{
		$controller = $this->controller_stub;
		$this->assertInstanceOf('ICanBoogie\View\View', $controller->view);
	}

	public function test_should_throw_exception_if_template_doesnot_exists()
	{
		$template = 'undefined' . uniqid();

		$view = $this
			->getMockBuilder('ICanBoogie\View\View')
			->setConstructorArgs([ $this->controller_stub ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();

		$view
			->expects($this->once())
			->method('get_template')
			->willReturn($template);

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
			->getMockBuilder('ICanBoogie\View\View')
			->setConstructorArgs([ $this->controller_stub ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();

		$view
			->expects($this->once())
			->method('get_layout')
			->willReturn($template);

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
			->getMockBuilder('ICanBoogie\View\View')
			->setConstructorArgs([ $this->controller_stub ])
			->setMethods([ 'get_template', 'get_layout' ])
			->getMock();

		$view
			->expects($this->once())
			->method('get_template')
			->willReturn('decorated');

		$content = 'MYCONTENT' . uniqid();
		$v1 = 'V1' . uniqid();
		$v2 = 'V2' . uniqid();
		$view->content = $content;
		$view['v1'] = $v1;
		$view['v2'] = $v2;
		$view->decorate_with('decorator');

		$expected = <<<EOT
<DECORATED>---
CONTENT: $content|
V1: $v1|
V2: $v2|
===
</DECORATED>

EOT;

		$this->assertEquals($expected, $view->render());
	}

	public function test_view_render()
	{
		$request = Request::from("/");
		$request->context->route = new Route($this->routes, '/', [ 'layout' => null ]);

		$controller = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function() use ($controller) {
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		/* @var $controller \ICanBoogie\Routing\Controller */
		$response = $controller($request);
		$this->assertEquals(self::FIXTURE_CONTENT, $response);
	}

	public function test_view_render_with_default_layout()
	{
		$request = Request::from("/");

		$controller = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function() use ($controller) {
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		/* @var $controller \ICanBoogie\Routing\Controller */

		$request->context->route = new Route($this->routes, '/', []);

		$response = $controller($request);
		$this->assertEquals(<<<EOT
<default>TESTING</default>

EOT
		, $response);
	}

	public function test_view_render_with_custom_layout()
	{
		$request = Request::from("/");
		$request->context->route = new Route($this->routes, '/', [ 'layout' => 'custom' ]);

		$controller = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(\Closure::bind(function() {

				$this->view->content = ViewTest::FIXTURE_CONTENT;

			}, $controller));

		/* @var $controller \ICanBoogie\Routing\Controller */
		$response = $controller($request);
		$this->assertEquals(<<<EOT
<custom>TESTING</custom>

EOT
		, $response);
	}

	public function test_controller_with_json_response()
	{
		$request = Request::from("/");
		$request->context->route = new Route($this->routes, '/', [ ]);

		$controller = $this
			->getMockBuilder('ICanBoogie\Routing\Controller')
			->setMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(\Closure::bind(function() {

				$this->view->content = [ 1 => "one", 2 => "two" ];
				$this->view->template = "json";
				$this->view->layout = null;

				$this->response->content_type = "application/json";

			}, $controller));

		/* @var $controller \ICanBoogie\Routing\Controller */
		$response = $controller($request);

		$this->assertInstanceOf('ICanBoogie\HTTP\Response', $response);
		$this->assertEquals("application/json", $response->content_type);
		$this->assertEquals('{"1":"one","2":"two"}', $response->body);
	}

	public function test_on_action_should_preserve_result()
	{
		$view = $this
			->getMockBuilder('ICanBoogie\View\View')
			->disableOriginalConstructor()
			->setMethods([ 'render' ])
			->getMock();
		$view
			->expects($this->never())
			->method('render');

		$result = uniqid();

		$event = $this
			->getMockBuilder('ICanBoogie\Routing\Controller\ActionEvent')
			->disableOriginalConstructor()
			->getMock();

		/* @var $event \ICanBoogie\Routing\Controller\ActionEvent */

		$event->result = $result;

		$on_action = new \ReflectionMethod($view, 'on_action');
		$on_action->setAccessible(true);
		$on_action->invoke($view, $event);

		$this->assertSame($result, $event->result);
	}
}
