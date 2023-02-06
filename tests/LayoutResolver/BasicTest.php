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

use ICanBoogie\Render\Renderer;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
    private MockObject & Renderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = $this->createMock(Renderer::class);
    }

    /**
     * @dataProvider provide_resolve_layout
     */
    public function test_resolve_layout(callable $setup, Route $route, string $expected): void
    {
        $setup($this->renderer);
        $actual = $this->makeSUT()->resolve_layout($route);

        $this->assertEquals($expected, $actual);
    }

    public static function provide_resolve_layout(): array // @phpstan-ignore-line
    {
        $no_setup = fn(MockObject&Renderer $renderer) => $renderer;

        return [

            "default" => [
                $no_setup,
                new Route('/madonna', 'madonna'),
                LayoutResolver::DEFAULT_LAYOUT
            ],

            "page" => [
                fn(MockObject&Renderer $renderer) => $renderer
                    ->method('resolve_template')
                    ->with('@page')
                    ->willReturn("whatever"),
                new Route('/madonna', 'madonna'),
                LayoutResolver::PAGE_LAYOUT
            ],

            "home" => [
                fn(MockObject&Renderer $renderer) => $renderer
                    ->method('resolve_template')
                    ->with('@home')
                    ->willReturn("whatever"),
                new Route('/', 'madonna'),
                LayoutResolver::HOME_LAYOUT
            ],

            "admin" => [
                $no_setup,
                new Route('/', 'admin:madonna'),
                LayoutResolver::ADMIN_LAYOUT
            ],

            "from route action prefix" => [
                $no_setup,
                new Route('/admin/articles', 'admin:articles:list'),
                LayoutResolver::ADMIN_LAYOUT
            ]

        ];
    }

    private function makeSUT(): LayoutResolver
    {
        return new LayoutResolver\Basic($this->renderer);
    }
}
