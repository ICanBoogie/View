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

use ArrayAccess;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\EventCollectionProvider;
use ICanBoogie\HTTP\Responder;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\TemplateName;
use ICanBoogie\Render\TemplateNotFound;
use ICanBoogie\Routing\Controller;
use JsonSerializable;

use function array_key_exists;
use function array_merge;
use function array_reverse;

/**
 * A view.
 *
 * @property-read Responder $responder The responder invoking the view.
 * @property-read Renderer $renderer
 * @property-read array $variables The variables to pass to the template.
 * @property mixed $content The content of the view.
 * @property string $layout The name of the layout that should decorate the content.
 * @property string $template The name of the template that should render the content.
 * @property-read callable[] $layout_resolvers @internal
 * @property-read callable[] $template_resolvers @internal
 */
class View implements ArrayAccess, JsonSerializable
{
	/**
	 * @uses get_responder
	 * @uses get_renderer
	 * @uses get_variables
	 * @uses get_content
	 * @uses set_content
	 * @uses lazy_get_template
	 * @uses get_template_resolvers
	 * @uses lazy_get_layout
	 * @uses get_layout_resolvers
	 */
	use AccessorTrait;

	public const TEMPLATE_TYPE_VIEW = 1;
	public const TEMPLATE_TYPE_LAYOUT = 2;
	public const TEMPLATE_TYPE_PARTIAL = 3;

	public const TEMPLATE_PREFIX_VIEW = '';
	public const TEMPLATE_PREFIX_LAYOUT = '@';
	public const TEMPLATE_PREFIX_PARTIAL = '_';

	private Responder $responder;

	protected function get_responder(): Responder
	{
		return $this->responder;
	}

	private Renderer $renderer;

	protected function get_renderer(): Renderer
	{
		return $this->renderer;
	}

	/**
	 * View's variables.
	 *
	 * @var array<string, mixed>
	 */
	private array $variables = [];

	/**
	 * @return array<string, mixed>
	 */
	protected function get_variables(): array
	{
		return $this->variables;
	}

	/**
	 * @see $content
	 */
	protected function get_content(): mixed
	{
		return $this->variables['content'] ?? null;
	}

	/**
	 * @see $content
	 */
	protected function set_content(mixed $content): void
	{
		$this->variables['content'] = $content;
	}

	/**
	 * @var array
	 */
	private array $decorators = [];

	/**
	 * Return the name of the template.
	 *
	 * The template name is resolved as follows:
	 *
	 * - The `template` property of the route.
	 * - The `template` property of the controller.
	 * - The `{$controller->name}/{$controller->action}`, if the controller has an `action`
	 * property.
	 */
	protected function lazy_get_template(): ?string
	{
		$controller = $this->responder;

		foreach ($this->template_resolvers as $provider) {
			try {
				return $provider($controller);
			} catch (PropertyNotDefined $e) {
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
	 */
	protected function lazy_get_layout(): ?string
	{
		$responder = $this->responder;

		foreach ($this->layout_resolvers as $resolver) {
			try {
				return $resolver($responder);
			} catch (PropertyNotDefined $e) {
				#
				# Resolver failed, we continue with the next.
				#
			}
		}

		if (str_starts_with($responder->route->id, "admin:")) {
			return 'admin';
		}

		if ($responder->route->pattern === "/" && $this->resolve_template('home', self::TEMPLATE_PREFIX_LAYOUT)) {
			return 'home';
		}

		if ($this->resolve_template('page', self::TEMPLATE_PREFIX_LAYOUT)) {
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
	 */
	public function __construct(Responder $responder, Renderer $renderer)
	{
		$this->responder = $responder;
		$this->renderer = $renderer;
		$this['view'] = $this;

		EventCollectionProvider::provide()->attach_to(
			$responder,
			function (Controller\ActionEvent $event, Responder $target) {
				$this->on_action($event);
			}
		);
	}

	/**
	 * @inheritdoc
	 *
	 * Returns an array with the following keys: `template`, `layout`, and `variables`.
	 *
	 * @return array{ 'template': string, 'layout': string, 'variables': array }
	 */
	public function jsonSerialize(): array
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
		return array_key_exists($offset, $this->variables);
	}

	/**
	 * @inheritdoc
	 *
	 * @throws OffsetNotDefined if the offset is not defined.
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new OffsetNotDefined([$offset, $this]);
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
	 * @return $this
	 */
	public function assign(array $variables): self
	{
		$this->variables = array_merge($this->variables, $variables);

		return $this;
	}

	/**
	 * Resolve a template pathname from its name and type.
	 *
	 * @param string $name Name of the template.
	 * @param string $prefix Template prefix.
	 * @param string[] $tried Reference to an array where tried paths are collected.
	 */
	protected function resolve_template(string $name, string $prefix, array &$tried = []): string|null
	{
		if ($prefix) {
			$name = TemplateName::from($name)->with_prefix($prefix);
		}

		try {
			return $this->renderer->resolve_template($name);
		} catch (TemplateNotFound $e) {
			$tried = $e->tried;

			return null;
		}
	}

	/**
	 * Add a template to decorate the content with.
	 */
	public function decorate_with(string $template): void
	{
		$this->decorators[] = $template;
	}

	/**
	 * Decorate the content.
	 */
	protected function decorate(mixed $content): string
	{
		$decorators = array_reverse($this->decorators);

		foreach ($decorators as $template) {
			$content = $this->renderer->render([

				Renderer::OPTION_CONTENT => $content,
				Renderer::OPTION_LAYOUT => $template

			]);
		}

		return $content;
	}

	public function render(): string
	{
		return $this->decorate(
			$this->renderer->render([

				Renderer::OPTION_CONTENT => $this->content,
				Renderer::OPTION_TEMPLATE => $this->template,
				Renderer::OPTION_LAYOUT => $this->layout,
				Renderer::OPTION_LOCALS => $this->variables

			])
		);
	}

	/**
	 * Render a partial.
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
		if ($event->result !== null) {
			return;
		}

		new View\BeforeRenderEvent($this, $event->result);

		if ($event->result !== null) {
			return;
		}

		$event->result = $this->render();
	}

	/**
	 * Ensures the array does not include our instance.
	 */
	private function ensure_without_this(array $array): array
	{
		foreach ($array as $key => $value) {
			if ($value === $this) {
				unset($array[$key]);
			}
		}

		return $array;
	}
}
