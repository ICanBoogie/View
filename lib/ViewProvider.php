<?php

namespace ICanBoogie\View;

use ICanBoogie\Routing\ControllerAbstract;

interface ViewProvider
{
    /**
     * Provides a new view for a controller.
     */
    public function view_for_controller(ControllerAbstract $controller): View;
}
