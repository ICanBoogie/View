<?php

namespace ICanBoogie\View\View;

use ICanBoogie\Event;
use ICanBoogie\View\View;

/**
 * Event class for the `ICanBoogie\View\View::render:before` event.
 *
 * Event hooks may use this event to alter the view before it is rendered, or provide a cached
 * result.
 */
final class BeforeRenderEvent extends Event
{
    public ?string $result;

    public function __construct(View $target, ?string &$result)
    {
        $this->result = &$result;

        parent::__construct($target);
    }
}
