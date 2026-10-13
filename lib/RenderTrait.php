<?php

namespace ICanBoogie\View;

/**
 * A trait for controllers.
 *
 * The using class must provide a `view_provider` property of type {@link ViewProvider}.
 * Actions return the view, which turns itself into a response, see
 * {@see \ICanBoogie\Routing\IntoResponse}.
 */
trait RenderTrait
{
    /**
     * Creates a view.
     *
     * The `template` and the `layout` are pre-determined by the view provider when the view is
     * turned into a response, but can be overloaded.
     *
     * @param array<string, mixed>|null $locals
     */
    private function view(
        mixed $content = null,
        ?string $template = null,
        ?string $layout = null,
        ?array $locals = null,
        ?string $content_type = null,
    ): View {
        $view = $this->view_provider->view($this);

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
        if ($content_type !== null) {
            $view->content_type = $content_type;
        }

        return $view;
    }
}
