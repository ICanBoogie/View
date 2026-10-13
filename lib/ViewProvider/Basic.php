<?php

namespace ICanBoogie\View\ViewProvider;

use ICanBoogie\HTTP\Request;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;
use ICanBoogie\View\View;
use ICanBoogie\View\ViewProvider;

use function ICanBoogie\emit;

final class Basic implements ViewProvider
{
    public function __construct(
        private readonly Renderer $renderer,
        private readonly LayoutResolver $layout_resolver,
    ) {
    }

    public function view(object $controller): View
    {
        return new View(
            $this->renderer,
            fn(View $view, Request $request) => $this->prepare($view, $request, $controller),
        );
    }

    private function prepare(View $view, Request $request, object $controller): void
    {
        $route = $request->context->get(Route::class);

        $view->template ??= $this->resolve_template($route);
        $view->layout ??= $this->resolve_layout($route);

        emit(new View\AlterEvent($view, $controller, $request));
    }

    private function resolve_template(Route $route): string
    {
        return strtr($route->action, Route::ACTION_SEPARATOR, '/');
    }

    private function resolve_layout(Route $route): string
    {
        return $this->layout_resolver->resolve_layout($route);
    }
}
