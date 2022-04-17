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

use Closure;
use ICanBoogie\EventCollection;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\HTTP\Response;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Controller;
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\ControllerBindings;
use ICanBoogie\View\View;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function random_bytes;
use function uniqid;

class ViewTest extends TestCase
{
	private const FIXTURE_CONTENT = "TESTING";

	static private function generate_bytes($length = 2048)
	{
		return random_bytes($length);
	}

	private MockObject|ControllerAbstract $controller;
	private Renderer $renderer;
	private EventCollection $events;

	public function setUp(): void
	{
		$this->controller = $this->createMock(ControllerAbstract::class);
		$this->renderer = get_renderer();
		$this->events = EventCollectionProvider::provide();
	}

	public function test_get_controller(): void
	{
		$this->assertSame($this->controller, $this->makeSTU()->controller);
	}

	public function test_get_renderer(): void
	{
		$this->assertSame($this->renderer, $this->makeSTU()->renderer);
	}

	public function test_get_variables(): void
	{
		$content = self::generate_bytes();
		$v1 = self::generate_bytes();
		$v2 = self::generate_bytes();

		$view = $this->makeSTU();
		$view->content = $content;
		$view['v1'] = $v1;
		$view['v2'] = $v2;

		$this->assertSame($content, $view->content);
		$this->assertSame($content, $view['content']);
		$this->assertEquals([ 'content' => $content, 'v1' => $v1, 'v2' => $v2, 'view' => $view ], $view->variables);
	}

	public function test_assign(): void
	{
		$content = uniqid();
		$v1 = uniqid();
		$v2 = uniqid();

		$view = $this->makeSTU();
		$view->assign(compact('content', 'v1', 'v2'));

		$this->assertSame($content, $view->content);
		$this->assertSame($content, $view['content']);
		$this->assertEquals([ 'content' => $content, 'v1' => $v1, 'v2' => $v2, 'view' => $view ], $view->variables);
	}

	/**
	 * @dataProvider provide_test_get_template
	 */
	public function test_get_template(ControllerAbstract $controller, ?string $expected)
	{
		$view = new View($controller, $this->renderer);

		$this->assertSame($expected, $view->template);
	}

	public function provide_test_get_template(): array
	{
		$cases = [];

		#

		$t = 'template' . uniqid();

		$c = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$c->template = $t;

		$cases['from template'] = [ $c, $t ];

		#

		$c_name = uniqid();
		$c_action = uniqid();

		$t = "$c_name/$c_action";

		$c = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'get_name' ])
			->addMethods([ 'get_action' ])
			->getMockForAbstractClass();

		$c->expects($this->once())
			->method('get_name')
			->willReturn($c_name);

		$c->expects($this->once())
			->method('get_action')
			->willReturn($c_action);

		$cases["from name and action"] = [ $c, $t ];

		#

		$c = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cases["no template"] = [ $c, null ];

		#

		return $cases;
	}

	/**
	 * @dataProvider provide_test_get_layout
	 */
	public function test_get_layout(ControllerAbstract $controller, ?string $expected): void
	{
		$renderer = $this
			->getMockBuilder(Renderer::class)
			->disableOriginalConstructor()
			->getMock();

		$view = new View($controller, $renderer);

		$this->assertSame($expected, $view->layout);
	}

	public function provide_test_get_layout(): array
	{
		return [
			'from controller' => (function () {
				$t = 'layout' . uniqid();

				$c = $this
					->getMockBuilder(ControllerAbstract::class)
					->disableOriginalConstructor()
					->getMockForAbstractClass();

				$c->layout = $t;

				return [ $c, $t ];
			})(),

			'from route action' => (function () {
				$t = 'admin';
				$c_route = new Route('/', 'admin:posts/index');

				$c = $this
					->getMockBuilder(ControllerAbstract::class)
					->disableOriginalConstructor()
					->onlyMethods([ 'get_route' ])
					->getMockForAbstractClass();

				$c->method('get_route')
					->willReturn($c_route);

				return [ $c, $t ];
			}) (),

//			'' => (function() {
//
//			}) ().
		];

		#
		# 'page'
		#

		return [

			[ $v1, $t1 ],
			[ $v2, $t2 ],
			[ $v3, $t3 ],
			$this->provide_test_get_layout_case3(),
			$this->provide_test_get_layout_case4(),
			$this->provide_test_get_layout_case5(),

		];
	}

	/*
	 * get_layout: home
	 */
	private function provide_test_get_layout_case3()
	{
		$expected = 'home';

		$route = new Route('/', 'posts:list');

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$renderer = $this
			->getMockBuilder(Renderer::class)
			->disableOriginalConstructor()
			->getMock();

//		$view = $this->getMockBuilder(View::class)
//			->setConstructorArgs([ $controller, $renderer ])
//			->onlyMethods([ 'resolve_template' ])
//			->getMock();
//
//		$view
//			->expects($this->once())
//			->method('resolve_template')
//			->with('home', View::TEMPLATE_PREFIX_LAYOUT)
//			->willReturn(true);


	}

	/*
	 * get_layout: page
	 */
	private function provide_test_get_layout_case4()
	{
		$route = new Route('/', 'action', id: 'posts/index');
//		$route = $this
//			->getMockBuilder(Route::class)
//			->disableOriginalConstructor()
//			->onlyMethods([ '__get' ])
//			->getMock();
//		$route->expects($this->any())
//			->method('__get')
//			->willReturnCallback(function($property) {
//
//				switch ($property)
//				{
//					case 'layout':
//						throw new PropertyNotDefined($property);
//					case 'id':
//						return 'posts/index';
//					case 'pattern':
//						return '/';
//                    default:
//                        throw new LogicException("Unexpected property: $property");
//				}
//
//			});

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'get_route' ])
			->getMockForAbstractClass();

		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$renderer = $this
			->getMockBuilder(Renderer::class)
			->disableOriginalConstructor()
			->getMock();

		$view = $this->getMockBuilder(View::class)
			->setConstructorArgs([ $controller, $renderer ])
			->onlyMethods([ 'resolve_template' ])
			->getMock();

		$view
			->expects($this->exactly(2))
			->method('resolve_template')
			->willReturnCallback(function ($name) {
				if ($name == 'home') {
					return false;
				} elseif ($name == 'page') {
					return true;
				}
				throw new LogicException("Unexpected name: $name");
			});

		return [ $view, 'page' ];
	}

	/*
	 * get_layout: default
	 */
	private function provide_test_get_layout_case5()
	{
		$route = new Route('/', 'action', id: 'posts/index');
//		$route = $this
//			->getMockBuilder(Route::class)
//			->disableOriginalConstructor()
//			->onlyMethods([ '__get' ])
//			->getMock();
//		$route->expects($this->any())
//			->method('__get')
//			->willReturnCallback(function($property) {
//
//				switch ($property)
//				{
//					case 'layout':
//						throw new PropertyNotDefined($property);
//					case 'id':
//						return 'posts/index';
//					case 'pattern':
//						return '/';
//                    default:
//                        throw new LogicException("Unexpected property: $property");
//				}
//
//			});

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'get_route' ])
			->getMockForAbstractClass();
		$controller->expects($this->any())
			->method('get_route')
			->willReturn($route);

		$renderer = $this
			->getMockBuilder(Renderer::class)
			->disableOriginalConstructor()
			->getMock();

		$view = $this->getMockBuilder(View::class)
			->setConstructorArgs([ $controller, $renderer ])
			->onlyMethods([ 'resolve_template' ])
			->getMock();

		$view
			->expects($this->exactly(2))
			->method('resolve_template')
			->willReturn(false);

		return [ $view, 'default' ];
	}

	public function test_array_interface()
	{
		$view = new View($this->controller, $this->renderer);
		$this->assertFalse(isset($view['content']));
		$expected = $this->generate_bytes();
		$view['content'] = $expected;
		$this->assertTrue(isset($view['content']));
		$this->assertEquals($expected, $view['content']);
		unset($view['content']);
		$this->assertFalse(isset($view['content']));
	}

	public function test_should_throw_exception_on_undefined_offset()
	{
		$view = new View($this->controller, $this->renderer);
		$this->expectException(OffsetNotDefined::class);
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
			->setConstructorArgs([ $this->controller, $this->renderer ])
			->addMethods([ 'get_template', 'get_layout' ])
			->getMock();
		$view
			->expects($this->once())
			->method('get_template')
			->willReturn($template);

		/* @var $view View */

		try {
			$view->render();
			$this->fail("Expected TemplateNotFound");
		} catch (TemplateNotFound $e) {
			$this->assertStringContainsString("no template matching", $e->getMessage());
			$this->assertStringContainsString($template, $e->getMessage());
		}
	}

	public function test_should_throw_exception_if_layout_doesnot_exists()
	{
		$template = 'undefined' . uniqid();

		$view = $this
			->getMockBuilder(View::class)
			->setConstructorArgs([ $this->controller, $this->renderer ])
			->addMethods([ 'get_template', 'get_layout' ])
			->getMock();
		$view
			->expects($this->once())
			->method('get_layout')
			->willReturn($template);

		/* @var $view View */

		try {
			$view->render();
			$this->fail("Expected TemplateNotFound");
		} catch (TemplateNotFound $e) {
			$this->assertStringContainsString($template, $e->getMessage());
		}
	}

	public function test_render_with_decorator()
	{
		$view = $this
			->getMockBuilder(View::class)
			->setConstructorArgs([ $this->controller, $this->renderer ])
			->addMethods([ 'get_template', 'get_layout' ])
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

	public function test_partial()
	{
		$expected = uniqid();
		$template = uniqid();
		$locals = [ uniqid() => uniqid() ];

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$renderer = $this
			->getMockBuilder(Renderer::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'render' ])
			->getMock();
		$renderer
			->expects($this->once())
			->method('render')
			->with(new RenderOptions(partial: $template, locals: $locals))
			->willReturn($expected);

		$view = new View($controller, $renderer);
		$this->assertSame($expected, $view->partial($template, $locals));
	}

	public function test_view_render()
	{
		$request = Request::from("/");
		$request->context->add(new Route('/', 'action'));

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->onlyMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function () use ($controller) {
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		$response = $controller->respond($request);
		$this->assertEquals(self::FIXTURE_CONTENT, $response);
	}

	public function test_view_render_with_default_layout()
	{
		$request = Request::from("/");

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->onlyMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(function () use ($controller) {
				$controller->view->content = ViewTest::FIXTURE_CONTENT;
			});

		$request->context->route = new Route('/', []);

		$response = $controller->respond($request);
		$this->assertEquals(
			<<<EOT
<default>TESTING</default>

EOT
			,
			$response
		);
	}

	public function test_view_render_with_custom_layout()
	{
		$request = Request::from("/");
//		$request->context->add(new Route('/', [ 'layout' => 'custom' ]));
		$request->context->add(new Route('/', 'action'));

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->onlyMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(
				Closure::bind(function () {
					$this->view->content = ViewTest::FIXTURE_CONTENT;
				}, $controller)
			);

		$response = $controller->respond($request);
		$this->assertEquals(
			<<<EOT
<custom>TESTING</custom>

EOT
			,
			$response
		);
	}

	public function test_controller_with_json_response()
	{
		$request = Request::from("/");
		$request->context->add(new Route('/', 'action'));

		$controller = $this
			->getMockBuilder(ControllerAbstract::class)
			->onlyMethods([ 'action' ])
			->getMockForAbstractClass();
		$controller
			->expects($this->once())
			->method('action')
			->willReturnCallback(
				Closure::bind(function () {
					$this->view->content = [ 1 => "one", 2 => "two" ];
					$this->view->template = "json";
					$this->view->layout = null;

					$this->response->content_type = "application/json";
				}, $controller)
			);

		$response = $controller->respond($request);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals("application/json", $response->content_type);
		$this->assertEquals('{"1":"one","2":"two"}', $response->body);
	}

	public function test_on_action_should_preserve_result()
	{
		$view = $this
			->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->onlyMethods([ 'render' ])
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

		$on_action = new ReflectionMethod($view, 'on_action');
		$on_action->setAccessible(true);
		$on_action->invoke($view, $event);

		$this->assertSame($result, $event->result);
	}

	public function test_on_action_should_preserve_before_render_event_result()
	{
		$expected_result = uniqid();
		$controller = $this->controller;
		$view = new View($controller, $this->renderer);

		$this->events->attach_to($view, function (View\BeforeRenderEvent $event, View $target) use ($expected_result) {
			$event->result = $expected_result;
		});

		$result = null;

		new Controller\ActionEvent($controller, $result);

		$this->assertEquals($expected_result, $result);
	}

	public function test_should_remove_this_during_json_serializ_if_view()
	{
		$view = new View($this->controller, $this->renderer);
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
		$view = new View($this->controller, $this->renderer);
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

	private function makeSTU(): View
	{
		return new View($this->controller, $this->renderer);
	}
}
