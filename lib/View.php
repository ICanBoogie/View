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
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
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
class View implements ArrayAccess, JsonSerializable, Stringable
{
    public const LOCAL_CONTENT = 'content';
    public const LOCAL_VIEW = 'view';

    /**
     * The content to render.
     */
    public mixed $content = null;

    /**
     * The name of the template that should render the content.
     */
    public string $template;

    /**
     * The name of the layout that should decorate the content.
     */
    public ?string $layout = null;

    /**
     * View's variables.
     *
     * @var array<string, mixed>
     */
    public array $locals = [];

    public function __construct(
        public readonly Renderer $renderer,
    ) {
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @inheritdoc
     *
     * @return array{ content: mixed, template: string, layout: ?string, locals: array<string, mixed> }
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
        assert(is_string($offset));

        return array_key_exists($offset, $this->locals);
    }

    /**
     * @param string $offset
     *
     * @throws OffsetNotDefined if the offset is not defined.
     */
    public function offsetGet(mixed $offset): mixed
    {
        assert(is_string($offset));

        if (!$this->offsetExists($offset)) {
            throw new OffsetNotDefined([ $offset, $this ]);
        }

        return $this->locals[$offset];
    }

    /**
     * @param string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_string($offset));

        $this->locals[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        assert(is_string($offset));

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
     * Render the content with a simple partial template.
     *
     * @param array<string, mixed> $locals
     */
    public function partial(mixed $content, string $template, array $locals = []): string
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
