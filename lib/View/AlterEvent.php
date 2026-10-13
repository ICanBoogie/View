<?php

namespace ICanBoogie\View\View;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\View\View;

/**
 * Emitted when a view is turned into a response, after its template and layout were resolved.
 *
 * The controller that created the view is the sender of the event, so that hooks can be attached to
 * specific controllers.
 */
final class AlterEvent extends Event
{
    /**
     * @param View $view
     *     The view to alter.
     * @param object $controller
     *     The controller that created the view.
     * @param Request $request
     *     The request being responded to. Its context has the route, which can be useful to alter
     *     the view according to the action.
     */
    public function __construct(
        public readonly View $view,
        object $controller,
        public readonly Request $request,
    ) {
        parent::__construct($controller);
    }
}
