<?php

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
