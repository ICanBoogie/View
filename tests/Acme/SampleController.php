<?php

namespace Test\ICanBoogie\View\Acme;

use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\View\RenderTrait;
use ICanBoogie\View\ViewProvider;

abstract class SampleController extends ControllerAbstract
{
    use RenderTrait {
        view as protected;
        render as protected;
    }

    public function __construct(
        private readonly ViewProvider $view_provider
    ) {
    }
}
