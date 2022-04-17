<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\View\LayoutResolver;

use Closure;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;
use ICanBoogie\View\View;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
	private MockObject|ControllerAbstract $controller;
	private MockObject|Renderer $renderer;

	protected function setUp(): void
	{
		parent::setUp();

		$this->controller = new class() extends ControllerAbstract {
			public ?string $layout = null;
			public ?Route $route = null;

			protected function action(Request $request): mixed
			{
				throw new LogicException("should not be called");
			}
		};

		$this->renderer = $this->createMock(Renderer::class);
	}

	/**
	 * @dataProvider provide_resolve_layout
	 */
	public function test_resolve_layout(Closure $case, string $expected): void
    {
		$case->call($this);

		$actual = $this->makeSTU()->resolve_layout($this->makeView());

		$this->assertEquals($expected, $actual);
    }

	public function provide_resolve_layout(): array
	{
		return [

			"default" => [
				function () {
					$this->controller->route = new Route('/madonna', 'madonna');
				},
				LayoutResolver::DEFAULT_LAYOUT
			],

			"page" => [
				function () {
					$this->controller->route = new Route('/madonna', 'madonna');
					$this->renderer
						->method('resolve_template')
						->with('@page')
						->willReturn("whatever");
				},
				LayoutResolver::PAGE_LAYOUT
			],

			"home" => [
				function () {
					$this->controller->route = new Route('/', 'madonna');
					$this->renderer
						->method('resolve_template')
						->with('@home')
						->willReturn("whatever");
				},
				LayoutResolver::HOME_LAYOUT
			],

			"admin" => [
				function () {
					$this->controller->route = new Route('/', 'admin:madonna');
				},
				LayoutResolver::ADMIN_LAYOUT
			],

			"from the controller" => [
				function () {
					$this->controller->layout = "my-layout";
				},
				"my-layout"
			],

			"from route action prefix" => [
				function () {
					$this->controller->route = new Route('/admin/articles', 'admin:articles:list');
				},
				"admin"
			]

		];
	}

	private function makeView(): View
	{
		return new View($this->controller, $this->renderer);
	}

	private function makeSTU(): LayoutResolver
	{
		return new LayoutResolver\Basic($this->renderer);
	}
}
