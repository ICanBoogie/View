<?php

namespace Test\ICanBoogie\View\ViewProvider;

use ICanBoogie\Event;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;
use ICanBoogie\View\View;
use ICanBoogie\View\ViewProvider\Basic;
use Test\ICanBoogie\View\Acme\ArticlesController;
use Test\ICanBoogie\View\Acme\PagesController;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @param non-empty-string $action
     */
    #[DataProvider('provide_resolution')]
    public function test_resolution(
        string $pattern,
        string $action,
        ?string $page_layout,
        string $expected_template,
        string $expected_layout,
    ): void {
        $this->renderer
            ->method('resolve_template')
            ->willReturnCallback(
                fn(string $name) => $name === $page_layout ? 'whatever' : throw new TemplateNotFound($name, [])
            );

        /** @var RenderOptions|null $options */
        $options = null;
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (mixed $content, RenderOptions $o) use (&$options) {
                $options = $o;

                return 'rendered';
            });

        $request = Request::from($pattern);
        $request->context->add(new Route($pattern, $action));

        $response = $this->makeSUT()->view($this)->into_response($request);

        $this->assertNotNull($options);
        $this->assertSame($expected_template, $options->template);
        $this->assertSame($expected_layout, $options->layout);
        $this->assertSame('rendered', $response->body);
    }

    /**
     * @return array<string, array{ string, non-empty-string, ?string, string, string }>
     */
    public static function provide_resolution(): array
    {
        return [

            "action" => ['/articles/1', 'articles:show', null, 'articles/show', 'default'],
            "nested action" => ['/a/b', 'a:b:c', null, 'a/b/c', 'default'],
            "admin" => ['/admin/articles', 'admin:articles:list', null, 'admin/articles/list', 'admin'],
            "home" => ['/', 'home', '@home', 'home', 'home'],
            "page" => ['/about', 'pages:show', '@page', 'pages/show', 'page'],

        ];
    }

    public function test_explicit_template_and_layout_are_preserved(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                null,
                $this->callback(function (RenderOptions $o) {
                    $this->assertSame('mytemplate', $o->template);
                    $this->assertSame('mylayout', $o->layout);

                    return true;
                })
            )
            ->willReturn('');

        $request = Request::from('/');
        $request->context->add(new Route('/', 'articles:show'));

        $view = $this->makeSUT()->view($this);
        $view->template = 'mytemplate';
        $view->layout = 'mylayout';
        $view->into_response($request);
    }

    public function test_alter_event_is_sent_by_the_controller(): void
    {
        $this->renderer->method('render')->willReturn('');

        $request = Request::from('/');
        $request->context->add(new Route('/', 'articles:show'));

        $articles = new ArticlesController();
        $captured = [];
        $detach = EventCollectionProvider::provide()->attach(
            View\AlterEvent::for(ArticlesController::class),
            function (Event $event) use (&$captured) {
                if (!$event instanceof View\AlterEvent) {
                    return;
                }

                $view = $event->view;
                $captured = [ $event->sender, $event->request, $view->template, $view->layout ];
                $view->layout = 'altered';
            }
        );

        try {
            $view = $this->makeSUT()->view($articles);
            $view->into_response($request);

            $other_view = $this->makeSUT()->view(new PagesController());
            $other_view->into_response($request);
        } finally {
            $detach();
        }

        $this->assertSame([ $articles, $request, 'articles/show', 'default' ], $captured);
        $this->assertSame('altered', $view->layout);
        $this->assertSame('default', $other_view->layout);
    }

    private function makeSUT(): Basic
    {
        return new Basic($this->renderer, new LayoutResolver\Basic($this->renderer));
    }
}
