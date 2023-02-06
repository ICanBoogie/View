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

use ICanBoogie\Routing\ControllerAbstract;

interface ViewProvider
{
    /**
     * Provides a new view for a controller.
     */
    public function view_for_controller(ControllerAbstract $controller): View;
}
