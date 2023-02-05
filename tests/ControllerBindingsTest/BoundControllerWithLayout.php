<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\View\ControllerBindingsTest;

use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\View\ControllerBindings as ViewBindings;

abstract class BoundControllerWithLayout extends ControllerAbstract
{
    use ViewBindings;

    protected function get_layout(): string
    {
        return 'my-layout';
    }
}
