<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        // @phpstan-ignore-next-line
        private readonly ViewProvider $view_provider
    ) {
    }
}
