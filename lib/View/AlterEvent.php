<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\View\View;

use ICanBoogie\Event;
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\View\View;

/**
 * Listener may use this event to alter the engine collection.
 */
final class AlterEvent extends Event
{
    /**
     * @param ControllerAbstract $controller
     *     The controller can be useful to alter the view. e.g. adjusting locals according to a route action.
     */
    public function __construct(
        View $target,
        public readonly ControllerAbstract $controller
    ) {
        parent::__construct($target);
    }
}
