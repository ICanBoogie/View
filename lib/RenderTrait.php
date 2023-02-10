<?php

namespace ICanBoogie\View;

use ICanBoogie\Routing\ControllerAbstract;

use function assert;

/**
 * A trait for {@link ControllerAbstract}.
 */
trait RenderTrait
{
    /**
     * Creates a view.
     *
     * The `template` and the `layout` are pre-determined by the view provider,
     * but can be overloaded.
     *
     * @param array<string, mixed>|null $locals
     */
    private function view(
        mixed $content = null,
        string $template = null,
        string $layout = null,
        array $locals = null,
    ): View {
        assert($this instanceof ControllerAbstract);
        assert(($this->view_provider ?? null) instanceof ViewProvider);

        $view = $this->view_provider->view_for_controller($this);

        if ($content !== null) {
            $view->content = $content;
        }
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

    /**
     * Creates a view with {@link view()} and renders a string.
     *
     * @param array<string, mixed>|null $locals
     */
    private function render_to_string(
        mixed $content = null,
        string $template = null,
        string $layout = null,
        array $locals = null,
    ): string {
        return $this->view(
            content: $content,
            template: $template,
            layout: $layout,
            locals: $locals,
        )->render();
    }

    /**
     * Creates a view with {@link view()} and renders as response's body.
     *
     * @param array<string, mixed>|null $locals
     */
    private function render(
        mixed $content = null,
        string $template = null,
        string $layout = null,
        array $locals = null,
    ): void {
        $this->response->body = $this->view(
            content: $content,
            template: $template,
            layout: $layout,
            locals: $locals,
        )->render();
    }
}
