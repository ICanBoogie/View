<?php

namespace Test\ICanBoogie\View\Acme;

use ICanBoogie\View\RenderTrait;
use ICanBoogie\View\ViewProvider;

abstract class SampleController
{
    use RenderTrait {
        view as protected;
    }

    public function __construct(
        private readonly ViewProvider $view_provider
    ) {
    }
}
