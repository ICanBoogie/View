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
use ICanBoogie\View\View;

/**
 * Listener may use this event to alter the engine collection.
 */
final class AlterEvent extends Event
{
	public function __construct(View $target)
	{
		parent::__construct($target);
	}
}
