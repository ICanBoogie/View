<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\View;

use ICanBoogie\OffsetNotDefined;
use ICanBoogie\Render\Renderer;
use ICanBoogie\Render\RenderOptions;
use ICanBoogie\View\View;
use PHPUnit\Framework\TestCase;

use function random_bytes;
use function uniqid;

final class ViewTest extends TestCase
{
    private static function generate_bytes(): string
    {
        return random_bytes(2048);
    }

    private Renderer $renderer;

    public function setUp(): void
    {
        $this->renderer = get_renderer();
    }

    public function test_get_renderer(): void
    {
        $this->assertSame($this->renderer, $this->makeSUT()->renderer);
    }

    public function test_assign(): void
    {
        $content = uniqid();
        $v1 = uniqid();
        $v2 = uniqid();

        $view = $this->makeSUT();
        $view->content = $content;
        $view->assign(compact('v1', 'v2'));

        $this->assertSame($content, $view->content);
        $this->assertArrayNotHasKey('content', $view);
        $this->assertEquals([ 'v1' => $v1, 'v2' => $v2 ], $view->locals);
    }

    public function test_array_interface(): void
    {
        $k = uniqid();
        $view = $this->makeSUT();
        $this->assertFalse(isset($view[$k]));
        $expected = $this->generate_bytes();
        $view[$k] = $expected;
        $this->assertTrue(isset($view[$k]));
        $this->assertEquals($expected, $view[$k]);
        unset($view[$k]);
        $this->assertFalse(isset($view[$k]));
    }

    public function test_should_throw_exception_on_undefined_offset(): void
    {
        $view = $this->makeSUT();
        $this->expectException(OffsetNotDefined::class);
        // @phpstan-ignore-next-line
        $view[uniqid()];
    }

    public function test_render_with_decorator(): void
    {
        $view = $this->makeSUT();
        $view->template = 'decorated';

        $content = 'MYCONTENT' . uniqid();
        $v1 = 'V1' . uniqid();
        $v2 = 'V2' . uniqid();
        $view->content = $content;
        $view['v1'] = $v1;
        $view['v2'] = $v2;
        $view->decorate_with('decorator');
        $view_class = get_class($view);

        $expected = <<<EOT
        <DECORATED>---
        CONTENT: $content|
        V1: $v1|
        V2: $v2|
        VIEW: $view_class|
        ===
        </DECORATED>

        EOT;

        $this->assertEquals($expected, $view->render());
    }

    public function test_partial(): void
    {
        $content = new class () {
        };
        $template = 'template' . uniqid();
        $locals = [ uniqid() => uniqid() ];
        $expected = 'expected' . uniqid();

        $renderer = $this
            ->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'render' ])
            ->getMock();
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $content,
                $this->callback(function (RenderOptions $o) use ($template, $locals, &$view) {
                    $this->assertNull($o->template);
                    $this->assertNull($o->layout);
                    $this->assertEquals($template, $o->partial);
                    $this->assertSame([ View::LOCAL_VIEW => $view ] + $locals, $o->locals);

                    return true;
                })
            )
            ->willReturn($expected);

        $view = new View($renderer);

        $actual = $view->partial($template, $content, $locals);

        $this->assertSame($expected, $actual);
    }

    public function test_should_remove_preserve_this_during_json_serialize_if_not_view(): void
    {
        $view = $this->makeSUT();
        $view->template = $template = uniqid();
        $view->layout = $layout = uniqid();
        $view['this'] = $that = (object)[ 'property' => uniqid() ];
        $view['var'] = $var = uniqid();

        $array = $view->jsonSerialize();
        $this->assertEquals($template, $array['template']);
        $this->assertEquals($layout, $array['layout']);
        $this->assertEquals($var, $array['locals']['var']);
        $this->assertArrayHasKey('this', $array['locals']);
        $this->assertEquals($that, $array['locals']['this']);
    }

    private function makeSUT(): View
    {
        return new View($this->renderer);
    }
}
