<?php

namespace ICanBoogie\View;

use ICanBoogie\Routing\Route;

interface LayoutResolver
{
    public const string HOME_PATH = '/';
    public const string HOME_LAYOUT = 'home';
    public const string PAGE_LAYOUT = 'page';
    public const string ADMIN_LAYOUT = 'admin';
    public const string ADMIN_ACTION_PREFIX = 'admin:';
    public const string DEFAULT_LAYOUT = 'default';

    /**
     * Resolves the layout template for the view.
     */
    public function resolve_layout(Route $route): string;
}
