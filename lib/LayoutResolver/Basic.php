<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\View\LayoutResolver;

use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\TemplateName;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\LayoutResolver;

use function str_starts_with;

final class Basic implements LayoutResolver
{
    public function __construct(
        private readonly Renderer $renderer
    ) {
    }

    public function resolve_layout(Route $route): string
    {
        return $this->from_route($route)
            ?? $this->from_page()
            ?? self::DEFAULT_LAYOUT;
    }

    private function from_route(Route $route): ?string
    {
        if (str_starts_with($route->action, self::ADMIN_ACTION_PREFIX)) {
            return self::ADMIN_LAYOUT;
        }

        if (
            $route->pattern == self::HOME_PATH &&
            $this->resolve_template(TemplateName::from(self::HOME_LAYOUT)->as_layout)
        ) {
            return self::HOME_LAYOUT;
        }

        return null;
    }

    private function from_page(): ?string
    {
        if ($this->resolve_template(TemplateName::from(self::PAGE_LAYOUT)->as_layout)) {
            return self::PAGE_LAYOUT;
        }

        return null;
    }

    private function resolve_template(string $name): ?string
    {
        try {
            return $this->renderer->resolve_template($name);
        } catch (TemplateNotFound) {
        }

        return null;
    }
}
