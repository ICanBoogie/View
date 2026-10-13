<?php

namespace Test\ICanBoogie\View;

use ICanBoogie\HTTP\Request;
use ICanBoogie\Routing\IntoResponse;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;
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

        $this->view_provider = new ViewProvider\Basic(
            get_renderer(),
            new class () implements LayoutResolver {
                public function resolve_layout(Route $route): string
                {
                    return 'default';
                }
            }
        );
    }

    public function test_view(): void
    {
        $controller = new class ($this->view_provider) extends SampleController {
            public function show(): View
            {
                return $this->view("MADONNA", template: 'mytemplate', layout: 'mylayout', locals: [
                    "a" => "a"
                ]);
            }
        };

        $view = $controller->show();

        $this->assertInstanceOf(IntoResponse::class, $view);
        $this->assertSame("MADONNA", $view->content);
        $this->assertSame("mytemplate", $view->template);
        $this->assertSame("mylayout", $view->layout);
        $this->assertSame([ "a" => "a" ], $view->locals);
    }

    public function test_content_type(): void
    {
        $request = Request::from('/');
        $request->context->add(new Route('/', 'json'));

        $view = $this->view_provider->view($this);
        $this->assertSame(
            'text/html',
            $view->into_response($request)->headers->content_type->type
        );

        $controller = new class ($this->view_provider) extends SampleController {
            public function feed(): View
            {
                return $this->view(template: 'json', content_type: 'application/rss+xml; charset=utf-8');
            }
        };

        $view = $controller->feed();
        $this->assertSame('application/rss+xml; charset=utf-8', $view->content_type);
        $this->assertSame(
            'application/rss+xml',
            $view->into_response($request)->headers->content_type->type
        );
    }

    public function test_defaults_are_resolved_from_route_when_turned_into_response(): void
    {
        $view = $this->view_provider->view($this);
        $this->assertNull($view->template);
        $this->assertNull($view->layout);

        $request = Request::from('/');
        $request->context->add(new Route('/', 'json'));

        $view->into_response($request);

        $this->assertSame('json', $view->template);
        $this->assertSame('default', $view->layout);
    }

    public function test_into_response_does_not_override_template(): void
    {
        $view = $this->view_provider->view($this);
        $view->template = 'json';

        $request = Request::from('/');
        $request->context->add(new Route('/', 'articles:show'));

        $response = $view->into_response($request);

        $this->assertSame('json', $view->template);
        $this->assertSame('text/html', $response->headers->content_type->type);
    }
}
