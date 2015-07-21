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

/**
 * {@link \ICanBoogie\Routing\Controller} prototype bindings.
 *
 * @property View $view
 * @property string $template
 * @property string $layout
 */
trait ControllerBindings
{
	/**
	 * @return View
	 */
	protected function lazy_get_view()
	{
		return parent::lazy_get_view();
	}
}
