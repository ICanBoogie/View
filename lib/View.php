<?php

namespace ICanBoogie\View;

use ArrayAccess;
use Closure;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
use ICanBoogie\Routing\IntoResponse;
use JsonSerializable;
use Stringable;

use function array_key_exists;
use function array_merge;
use function array_reverse;
use function assert;
use function is_string;

/**
 * A view.
 *
 * @implements ArrayAccess<string, mixed>
 */
class View implements ArrayAccess, JsonSerializable, Stringable, IntoResponse
{
    public const string LOCAL_CONTENT = 'content';
    public const string LOCAL_VIEW = 'view';

    /**
     * The content to render.
     */
    public mixed $content = null;

    /**
     * The name of the template that should render the content.
     */
    public ?string $template = null;

    /**
     * The name of the layout that should decorate the content.
     */
    public ?string $layout = null;

    /**
     * The content type of the response.
     */
    public string $content_type = 'text/html; charset=utf-8';

    /**
     * View's variables.
     *
     * @var array<string, mixed>
     */
    public array $locals = [];

    /**
     * @param (Closure(View, Request): void)|null $prepare
     *     Completes the view, with its default template and layout for instance, when it is turned
     *     into a response. The route of the request is available in its context.
     */
    public function __construct(
        public readonly Renderer $renderer,
        private readonly ?Closure $prepare = null,
    ) {
    }

    public function into_response(Request $request): Response
    {
        if ($this->prepare) {
            ($this->prepare)($this, $request);
        }

        return new Response($this->render(), headers: [

            'Content-Type' => $this->content_type,

        ]);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @inheritdoc
     *
     * @return array{ content: mixed, template: ?string, layout: ?string, locals: array<string, mixed> }
     */
    public function jsonSerialize(): array
    {
        return [

            'content' => $this->content,
            'template' => $this->template,
            'layout' => $this->layout,
            'locals' => $this->locals

        ];
    }

    /**
     * @param string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->locals);
    }

    /**
     * @param string $offset
     *
     * @throws OffsetNotDefined if the offset is not defined.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined(offset: $offset, container: $this);
        }

        return $this->locals[$offset];
    }

    /**
     * @param string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->locals[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->locals[$offset]);
    }

    /**
     * Assign multiple variables.
     *
     * @param array<string, mixed> $locals
     *
     * @return $this
     */
    public function assign(array $locals): self
    {
        $this->locals = array_merge($this->locals, $locals);

        return $this;
    }

    /**
     * Additional layout templates to decorate the rendered content with.
     *
     * @var string[]
     */
    private array $decorators = [];

    /**
     * Add a template to decorate the content with.
     */
    public function decorate_with(string $template): void
    {
        $this->decorators[] = $template;
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

        assert(is_string($content));

        return $content;
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
                    locals: [
                        self::LOCAL_CONTENT => $this->content,
                        self::LOCAL_VIEW => $this,
                    ] + $this->locals,
                )
            )
        );
    }

    /**
     * Renders a partial template.
     *
     * @param array<string, mixed> $locals
     */
    public function partial(string $template, mixed $content = null, array $locals = []): string
    {
        return $this->renderer->render(
            $content,
            new RenderOptions(
                partial: $template,
                locals: [
                    self::LOCAL_VIEW => $this,
                ] + $locals
            )
        );
    }
}
