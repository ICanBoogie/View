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

trait ViewTrait
{
    /**
     * @param array<string, mixed>|null $locals
     */
    private function view(
        mixed $content = null,
        string $template = null,
        string $layout = null,
        array $locals = null
    ): View {
        assert(($this->view_provider ?? null) instanceof ViewProvider);

        $view = $this->view_provider->view_for_controller($this);
        $view->content = $content;

        if ($template !== null) {
            $view->template = $template;
        }
        if ($layout !== null) {
            $view->layout = $layout;
        }
        if ($locals !== null) {
            $view->assign($locals);
        }

        return $view;
    }
}
