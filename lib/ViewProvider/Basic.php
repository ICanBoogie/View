<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\View\ViewProvider;

use ICanBoogie\Render\Renderer;
use ICanBoogie\Routing\ControllerAbstract;
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

    public function view_for_controller(ControllerAbstract $controller): View
    {
        $view = new View($this->renderer);

        emit(new View\AlterEvent($view, $controller));

        $route = $controller->route;
        $view->template ??= $this->resolve_template($route);
        $view->layout ??= $this->resolve_layout($route);

        return $view;
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
