<?php

namespace Test\ICanBoogie\View\Acme;

use ICanBoogie\View\ViewTrait;
use ICanBoogie\View\ViewProvider;

abstract class SampleController
{
    use ViewTrait {
        view as protected;
    }

    public function __construct(
        private readonly ViewProvider $view_provider
    ) {
    }
}
