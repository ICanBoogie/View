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
class BeforeRenderEvent extends Event
{
	const TYPE = 'render:before';

	/**
	 * @var string|null
	 */
	public $result;

	/**
	 * @param View $target
	 * @param string|null $result
	 */
	public function __construct(View $target, ?string &$result)
	{
		$this->result = &$result;

		parent::__construct($target, self::TYPE);
	}
}
