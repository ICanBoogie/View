<?php

namespace ICanBoogie\View;

interface ViewProvider
{
    /**
     * Provides a new view for a controller.
     *
     * The template and layout are resolved from the route when the view is turned into a response,
     * unless they were set.
     */
    public function view(object $controller): View;
}
