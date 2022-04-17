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
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
use ICanBoogie\Routing\Controller;
use ICanBoogie\Routing\ControllerAbstract;
use ICanBoogie\Routing\Route;
use JsonSerializable;

use function array_key_exists;
use function array_merge;
use function array_reverse;
use function strtr;

/**
 * A view.
 *
 * @property-read array $variables The variables to pass to the template.
 * @property mixed $content The content of the view.
 * @property string $layout The name of the layout that should decorate the content.
 * @property string $template The name of the template that should render the content.
 */
class View implements ArrayAccess, JsonSerializable
{
	/**
	 * @uses get_variables
	 * @uses get_content
	 * @uses set_content
	 * @uses lazy_get_template
	 * @uses lazy_get_layout
	 */
	use AccessorTrait;

	private readonly LayoutResolver\Basic $layout_resolver;

	/**
	 * View's variables.
	 *
	 * @var array<string, mixed>
	 */
	private array $variables = [];

	/**
	 * @return array<string, mixed>
	 */
	private function get_variables(): array
	{
		return $this->variables;
	}

	private function get_content(): mixed
	{
		return $this->variables['content'] ?? null;
	}

	private function set_content(mixed $content): void
	{
		$this->variables['content'] = $content;
	}

	/**
	 * Additional layout templates to decorate the rendered content with.
	 *
	 * @var string[]
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
	private function lazy_get_template(): ?string
	{
		$controller = $this->controller;

		foreach ($this->template_resolvers() as $provider) {
			try {
				return $provider($controller);
			} catch (PropertyNotDefined) {
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
	private function template_resolvers(): array
	{
		return [

			// ROUTES ALWAYS HAVE AN ACTION
			function ($controller) {
				$route = $controller->route;

				return strtr($route->action, Route::ACTION_SEPARATOR, '/');
			},

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
		return $this->layout_resolver->resolve_layout($this);
	}

	/**
	 * An event hook is attached to the `action` event of the controller for late rendering,
	 * which only happens if the response is `null`.
	 */
	public function __construct(
		public readonly ControllerAbstract $controller,
		public readonly Renderer $renderer,
		LayoutResolver $layout_resolver = null,
	) {
		$this->layout_resolver = $layout_resolver ?? new LayoutResolver\Basic($renderer);

		$this['view'] = $this;

		EventCollectionProvider::provide()->attach_to(
			$controller,
			function (Controller\ActionEvent $event, ControllerAbstract $target) {
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
	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->variables);
	}

	/**
	 * @inheritdoc
	 *
	 * @throws OffsetNotDefined if the offset is not defined.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		if (!$this->offsetExists($offset)) {
			throw new OffsetNotDefined([ $offset, $this ]);
		}

		return $this->variables[$offset];
	}

	/**
	 * @inheritdoc
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->variables[$offset] = $value;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetUnset(mixed $offset): void
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
	 * Add a template to decorate the content with.
	 */
	public function decorate_with(string $template): void
	{
		$this->decorators[] = $template;
	}

	/**
	 * Render the content with template, layout, and decorators.
	 */
	public function render(): string
	{
		return $this->decorate(
			$this->renderer->render(
				$this->content,
				new RenderOptions(
					template: $this->template,
					layout: $this->layout,
					locals: $this->variables,
				)
			)
		);
	}

	/**
	 * Render the content with a simple partial template.
	 */
	public function partial(mixed $content, string $template, array $locals = []): string
	{
		return $this->renderer->render(
			$content,
			new RenderOptions(partial: $template, locals: $locals)
		);
	}

	private function decorate(mixed $content): string
	{
		$decorators = array_reverse($this->decorators);

		foreach ($decorators as $template) {
			$content = $this->renderer->render(
				$content,
				new RenderOptions(layout: $template)
			);
		}

		return $content;
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
