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
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\View;
use ICanBoogie\View\ViewProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\View\Acme\SampleController;

final class ViewTraitTest extends TestCase
{
    private ViewProvider $view_provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view_provider = new class () implements ViewProvider {
            public function view_for_controller(ControllerAbstract $controller): View
            {
                return new View(get_renderer());
            }
        };
    }

    public function test_view(): void
    {
        $controller = new class ($this->view_provider) extends SampleController {
            protected function action(Request $request): View
            {
                return $this->view("MADONNA", template: 'mytemplate', layout: 'mylayout', locals: [
                    "a" => "a"
                ]);
            }
        };

        $request = Request::from('/');
        $request->context->add(new Route('/', 'articles:show'));

        $body = $controller->respond($request)->body;

        $this->assertInstanceOf(View::class, $body);
        $this->assertSame("MADONNA", $body->content);
        $this->assertSame("mytemplate", $body->template);
        $this->assertSame("mylayout", $body->layout);
        $this->assertSame([ "a" => "a" ], $body->locals);
    }
}
