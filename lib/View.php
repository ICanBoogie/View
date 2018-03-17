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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\TemplateName;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Controller;

/**
 * A view.
 *
 * @property-read Controller $controller The controller invoking the view.
 * @property-read Renderer $renderer The controller invoking the view.
 * @property-read array $variables The variables to pass to the template.
 * @property mixed $content The content of the view.
 * @property string $layout The name of the layout that should decorate the content.
 * @property string $template The name of the template that should render the content.
 * @property-read callable[] $layout_resolvers @internal
 * @property-read callable[] $template_resolvers @internal
 */
class View implements \ArrayAccess, \JsonSerializable
{
	use AccessorTrait;

	const TEMPLATE_TYPE_VIEW = 1;
	const TEMPLATE_TYPE_LAYOUT = 2;
	const TEMPLATE_TYPE_PARTIAL = 3;

	const TEMPLATE_PREFIX_VIEW = '';
	const TEMPLATE_PREFIX_LAYOUT = '@';
	const TEMPLATE_PREFIX_PARTIAL = '_';

	/**
	 * @var Controller
	 */
	private $controller;

	protected function get_controller(): Controller
	{
		return $this->controller;
	}

	/**
	 * @var Renderer
	 */
	private $renderer;

	protected function get_renderer(): Renderer
	{
		return $this->renderer;
	}

	/**
	 * View's variables.
	 *
	 * @var array
	 */
	private $variables = [];

	protected function get_variables(): array
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
	protected function lazy_get_template(): ?string
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
	protected function get_template_resolvers(): array
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
	 * @return string|null
	 */
	protected function lazy_get_layout(): ?string
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
	protected function get_layout_resolvers(): array
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
	 * @param Renderer $renderer
	 */
	public function __construct(Controller $controller, Renderer $renderer)
	{
		$this->controller = $controller;
		$this->renderer = $renderer;
		$this['view'] = $this;

		EventCollectionProvider::provide()->attach_to($controller, function (Controller\ActionEvent $event, Controller $target) {

			$this->on_action($event);

		});
	}

	/**
	 * @inheritdoc
	 *
	 * Returns an array with the following keys: `template`, `layout`, and `variables`.
	 */
	public function jsonSerialize()
	{
		return [

			'template' => $this->template,
			'layout' => $this->layout,
			'variables' => $this->ensure_without_this($this->variables)

		];
	}

	/**
	 * @inheritdoc
	 */
	public function offsetExists($offset)
	{
		return \array_key_exists($offset, $this->variables);
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
	 * Assign multiple variables.
	 *
	 * @param array $variables
	 *
	 * @return $this
	 */
	public function assign(array $variables): self
	{
		$this->variables = \array_merge($this->variables, $variables);

		return $this;
	}

	/**
	 * Resolve a template pathname from its name and type.
	 *
	 * @param string $name Name of the template.
	 * @param string $prefix Template prefix.
	 * @param array $tried Reference to an array where tried paths are collected.
	 *
	 * @return string|false
	 */
	protected function resolve_template(string $name, string $prefix, array &$tried = [])
	{
		if ($prefix)
		{
			$name = TemplateName::from($name)->with_prefix($prefix);
		}

		try
		{
			return $this->renderer->resolve_template($name);
		}
		catch (TemplateNotFound $e)
		{
			$tried = $e->tried;

			return null;
		}
	}

	/**
	 * Add a template to decorate the content with.
	 *
	 * @param string $template Name of the template.
	 */
	public function decorate_with($template): void
	{
		$this->decorators[] = $template;
	}

	/**
	 * Decorate the content.
	 *
	 * @param mixed $content The content to decorate.
	 *
	 * @return string
	 */
	protected function decorate($content): string
	{
		$decorators = array_reverse($this->decorators);

		foreach ($decorators as $template)
		{
			$content = $this->renderer->render([

				Renderer::OPTION_CONTENT => $content,
				Renderer::OPTION_LAYOUT => $template

			]);
		}

		return $content;
	}

	/**
	 * Render the view.
	 *
	 * @return string
	 */
	public function render(): string
	{
		return $this->decorate($this->renderer->render([

			Renderer::OPTION_CONTENT => $this->content,
			Renderer::OPTION_TEMPLATE => $this->template,
			Renderer::OPTION_LAYOUT => $this->layout,
			Renderer::OPTION_LOCALS => $this->variables

		]));
	}

	/**
	 * Render a partial.
	 *
	 * @param string $template
	 * @param array $locals
	 * @param array $options
	 *
	 * @return string
	 */
	public function partial(string $template, array $locals = [], array $options = []): string
	{
		return $this->renderer->render([

			Renderer::OPTION_PARTIAL => $template,
			Renderer::OPTION_LOCALS => $locals

		], $options);
	}

	/**
	 * Renders the view on `Controller::action` event.
	 *
	 * **Note:** The view is not rendered if the event's response is defined, which is the case
	 * when the controller obtained a result after its execution.
	 *
	 * @param Controller\ActionEvent $event
	 */
	protected function on_action(Controller\ActionEvent $event): void
	{
		if ($event->result !== null)
		{
			return;
		}

		new View\BeforeRenderEvent($this, $event->result);

		if ($event->result !== null)
		{
			return;
		}

		$event->result = $this->render();
	}

	/**
	 * Ensures the array does not include our instance.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private function ensure_without_this(array $array): array
	{
		foreach ($array as $key => $value)
		{
			if ($value === $this)
			{
				unset($array[$key]);
			}
		}

		return $array;
	}
}
