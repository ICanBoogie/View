<?php

namespace ICanBoogie\View;

/**
 * View exceptions implement this interface so that they can be easily recognized.
 *
 * <pre>
 * try
 * {
 *     // …
 * }
 * catch (\ICanBoogie\View\Exception $e)
 * {
 *     // a view exception
 * }
 * catch (\Exception $e
 * {
 *     // another type of exception
 * }
 * </pre>
 */
interface Exception
{
}
