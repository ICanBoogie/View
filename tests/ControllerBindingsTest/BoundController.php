<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\View\ControllerBindingsTest;

use ICanBoogie\Routing\Controller;
use ICanBoogie\View\ControllerBindings;

abstract class BoundController extends Controller
{
	use ControllerBindings;
}
