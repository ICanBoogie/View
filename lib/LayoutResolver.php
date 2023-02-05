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

interface LayoutResolver
{
    public const HOME_PATH = '/';
    public const HOME_LAYOUT = 'home';
    public const PAGE_LAYOUT = 'page';
    public const ADMIN_LAYOUT = 'admin';
    public const DEFAULT_LAYOUT = 'default';

    /**
     * Resolves the layout template for the view.
     */
    public function resolve_layout(View $view): string;
}
