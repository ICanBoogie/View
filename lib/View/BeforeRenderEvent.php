<?php

namespace ICanBoogie\View\View;

use ICanBoogie\Event;
use ICanBoogie\View\View;

/**
 * Event class for the `ICanBoogie\View\View::render:before` event.
 *
 * Event hooks may use this event to alter the view before it is rendered.
 *
 * @package ICanBoogie\View\View
 */
class BeforeRenderEvent extends Event
{
	public function __construct(View $target)
	{
		parent::__construct($target, 'render:before');
	}
}
