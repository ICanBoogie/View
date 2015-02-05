<?php

namespace ICanBoogie\View\View;

use ICanBoogie\Event;
use ICanBoogie\View\View;

class BeforeRender extends Event
{
	public function __construct(View $target)
	{
		parent::__construct($target, 'render:before');
	}
}
