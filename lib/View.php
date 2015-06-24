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

use ICanBoogie\Events;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\PrototypeTrait;
use ICanBoogie\Render\TemplateName;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Controller;

/**
 * A view.
 *
 * @property-read Controller $controller The controller invoking the view.
 * @property-read array $variables The variables to pass to the template.
 * @property mixed $content The content of the view.
 * @property string $layout The name of the layout that should decorate the content.
 * @property string $template The name of the template that should render the content.
 * @property-read callable[] $layout_resolvers @internal
 * @property-read callable[] $template_resolvers @internal
 */
class View implements \ArrayAccess
{
	const TEMPLATE_TYPE_VIEW = 1;
	const TEMPLATE_TYPE_LAYOUT = 2;
	const TEMPLATE_TYPE_PARTIAL = 3;

	const TEMPLATE_PREFIX_VIEW = '';
	const TEMPLATE_PREFIX_LAYOUT = '@';
	const TEMPLATE_PREFIX_PARTIAL = '_';

	static private $template_type_name = [

		self::TEMPLATE_TYPE_VIEW => "template",
		self::TEMPLATE_TYPE_LAYOUT => "layout",
		self::TEMPLATE_TYPE_PARTIAL=> "partial"

	];

	static private $template_prefix = [

		self::TEMPLATE_TYPE_VIEW => self::TEMPLATE_PREFIX_VIEW,
		self::TEMPLATE_TYPE_LAYOUT => self::TEMPLATE_PREFIX_LAYOUT,
		self::TEMPLATE_TYPE_PARTIAL=> self::TEMPLATE_PREFIX_PARTIAL

	];

	use PrototypeTrait;
	use ViewBindings;

	/**
	 * @var Controller
	 */
	private $controller;

	/**
	 * @see $controller
	 *
	 * @return Controller
	 */
	protected function get_controller()
	{
		return $this->controller;
	}

	/**
	 * View's variables.
	 *
	 * @var array
	 */
	private $variables = [];

	/**
	 * @see $variables
	 *
	 * @return array
	 */
	protected function get_variables()
	{
		return $this->variables;
	}

	/**
	 * @see $content
	 *
	 * @return mixed
	 */
	protected function get_content()
	{
		return isset($this->variables['content']) ? $this->variables['content'] : null;
	}

	/**
	 * @see $content
	 *
	 * @param mixed $content
	 */
	protected function set_content($content)
	{
		$this->variables['content'] = $content;
	}

	private $decorators = [];

	/**
	 * Return the name of the template.
	 *
	 * The template name is resolved as follows:
	 *
	 * - The `template` property of the route.
	 * - The `template` property of the controller.
	 * - The `{$controller->name}/{$controller->action}`, if the controller has an `action`
	 * property.
	 *
	 * @return string|null
	 */
	protected function lazy_get_template()
	{
		$controller = $this->controller;

		foreach ($this->template_resolvers as $provider)
		{
			try
			{
				return $provider($controller);
			}
			catch (PropertyNotDefined $e)
			{
				#
				# Resolver failed, we continue with the next.
				#
			}

		}

		return null;
	}

	/**
	 * Returns an array of callable used to resolve the {@link $template} property.
	 *
	 * @return callable[]
	 *
	 * @internal
	 */
	protected function get_template_resolvers()
	{
		return [

			function ($controller) {

				return $controller->route->template;

			},

			function ($controller) {

				return $controller->template;

			},

			function ($controller) {

				return $controller->name . "/" . $controller->action;

			}

		];
	}

	/**
	 * Returns the name of the layout.
	 *
	 * The layout name is resolved as follows:
	 *
	 * - The `layout` property of the route.
	 * - The `layout` property of the controller.
	 * - If the identifier of the route starts with "admin:", "admin" is returned.
	 * - If the route pattern is "/" and a "home" layout template is available, "home" is returned.
	 * - If the "@page" template is available, "page" is returned.
	 * - "default" is returned.
	 *
	 * @return string
	 */
	protected function lazy_get_layout()
	{
		$controller = $this->controller;

		foreach ($this->layout_resolvers as $resolver)
		{
			try
			{
				return $resolver($controller);
			}
			catch (PropertyNotDefined $e)
			{
				#
				# Resolver failed, we continue with the next.
				#
			}

		}

		if (strpos($controller->route->id, "admin:") === 0)
		{
			return 'admin';
		}

		if ($controller->route->pattern == "/" && $this->resolve_template('home', self::TEMPLATE_PREFIX_LAYOUT))
		{
			return 'home';
		}

		if ($this->resolve_template('page', self::TEMPLATE_PREFIX_LAYOUT))
		{
			return 'page';
		}

		return 'default';
	}

	/**
	 * Returns an array of callable used to resolve the {@link $template} property.
	 *
	 * @return callable[]
	 *
	 * @internal
	 */
	protected function get_layout_resolvers()
	{
		return [

			function ($controller) {

				return $controller->route->layout;

			},

			function ($controller) {

				return $controller->layout;

			}

		];
	}

	/**
	 * An event hook is attached to the `action` event of the controller for late rendering,
	 * which only happens if the response is `null`.
	 *
	 * @param Controller $controller The controller that invoked the view.
	 */
	public function __construct(Controller $controller)
	{
		$this->controller = $controller;

		Events::get()->attach_to($controller, function (Controller\ActionEvent $event, Controller $target) {

			$this->on_action($event);

		});
	}

	/**
	 * @inheritdoc
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->variables);
	}

	/**
	 * @inheritdoc
	 *
	 * @throws OffsetNotDefined if the offset is not defined.
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset))
		{
			throw new OffsetNotDefined([ $offset, $this ]);
		}

		return $this->variables[$offset];
	}

	/**
	 * @inheritdoc
	 */
	public function offsetSet($offset, $value)
	{
		$this->variables[$offset] = $value;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetUnset($offset)
	{
		unset($this->variables[$offset]);
	}

	/**
	 * Add a path to search for templates.
	 *
	 * @param string $path
	 * @param int $weight
	 */
	public function add_path($path, $weight=0)
	{
		$this->template_resolver->add_path($path, $weight);
	}

	/**
	 * Resolve a template pathname from its name and type.
	 *
	 * @param string $name Name of the template.
	 * @param string $prefix Template prefix.
	 * @param array $tries Reference to an array where tried path are collected.
	 *
	 * @return string|false
	 */
	protected function resolve_template($name, $prefix, &$tries = [])
	{
		$tries = $tries ?: [];

		if ($prefix)
		{
			$name = TemplateName::from($name);
			$name = $name->with_prefix($prefix);
		}

		$resolver = $this->template_resolver;

		return $resolver->resolve($name, $this->engines->extensions, $tries);
	}

	/**
	 * Add a template to decorate the content with.
	 *
	 * @param string $template Name of the template.
	 */
	public function decorate_with($template)
	{
		$this->decorators[] = $template;
	}

	/**
	 * Decorate the content.
	 *
	 * @param mixed $content The content to decorate.
	 *
	 * @return mixed
	 */
	protected function decorate($content)
	{
		$decorators = array_reverse($this->decorators);

		foreach ($decorators as $template)
		{
			$content = $this->render_with_template($content, $template, self::TEMPLATE_TYPE_LAYOUT);
		}

		return $content;
	}

	/**
	 * Render the view.
	 *
	 * @return string
	 */
	public function render()
	{
		$steps = [

			[ $this->template, self::TEMPLATE_TYPE_VIEW ],
			[ $this->layout, self::TEMPLATE_TYPE_LAYOUT ]

		];

		$content = $this->content;

		foreach ($steps as list($template, $type))
		{
			if (!$template)
			{
				continue;
			}

			$content = $this->render_with_template($content, $template, $type);
		}

		return $this->decorate($content);
	}

	/**
	 * Renders the content using a template.
	 *
	 * @param mixed $content The content to render.
	 * @param string $template Name of the template.
	 * @param string $type Type of the template.
	 *
	 * @return string
	 */
	protected function render_with_template($content, $template, $type)
	{
		$pathname = $this->resolve_template($template, self::$template_prefix[$type], $tries);

		if (!$pathname)
		{
			$type_name = self::$template_type_name[$type];

			throw new TemplateNotFound("There is no $type_name matching <q>$template</q>.", $tries);
		}

		list($thisArg, $variables) = $this->prepare_engine_args($content, $type);

		return $this->engines->render($pathname, $thisArg, $variables);
	}

	/**
	 * Prepares engine arguments.
	 *
	 * @param mixed $content
	 * @param string $type
	 *
	 * @return array
	 */
	protected function prepare_engine_args($content, $type)
	{
		$variables = $this->variables;

		if ($type == self::TEMPLATE_TYPE_LAYOUT)
		{
			$variables['content'] = $content;

			return [ $this, $variables ];
		}

		$variables['view'] = $this;

		return [ $content, $variables ];
	}

	/**
	 * Renders the view on `Controller::action` event.
	 *
	 * **Note:** The view is not rendered if the event's response is defined, which is the case
	 * when the controller obtained a result after its execution.
	 *
	 * @param Controller\ActionEvent $event
	 */
	protected function on_action(Controller\ActionEvent $event)
	{
		if ($event->result !== null)
		{
			return;
		}

		new View\BeforeRender($this);

		$event->result = $this->render();
	}
}
