# Getting started

A [View][] holds what is needed to render a page: a _content_, the name of a _template_ that presents
the content, the name of a _layout_ that decorates the result, and _locals_, the variables made
available to the templates. It implements [IntoResponse][], so an action of a
[stateless controller][] can return it, and the router turns it into a response.

Views are created by a [ViewProvider][], `ViewProvider\Basic` is the default implementation. It
needs a `Renderer` from [icanboogie/render][], and a `LayoutResolver`.

```php
<?php

use ICanBoogie\View\LayoutResolver;
use ICanBoogie\View\ViewProvider;

/* @var $renderer \ICanBoogie\Render\Renderer */

$view_provider = new ViewProvider\Basic($renderer, new LayoutResolver\Basic($renderer));
```

If you use the package with [ICanBoogie][], require [icanboogie/bind-view][] and let it deal with this.



## Returning a view from an action

The [ViewTrait][] adds a `view()` method to your controller, it creates a view for the controller with
the provider found in its `view_provider` property.

```php
<?php

namespace App\Articles;

use ICanBoogie\Routing\Attribute\Get;
use ICanBoogie\View\ViewTrait;
use ICanBoogie\View\View;
use ICanBoogie\View\ViewProvider;

final class ArticleController
{
    use ViewTrait;

    public function __construct(
        private readonly ViewProvider $view_provider,
        private readonly ArticleRepository $articles,
    ) {
    }

    #[Get('/articles')]
    public function list(): View
    {
        return $this->view(
            content: $this->articles->latest(10),
            locals: [ 'title' => "Last ten articles" ],
        );
    }
}
```

The controller doesn't know about the request, and neither does the view when it is created. The
template and the layout are resolved later, when the router turns the view into a response, from
the route that matched the request. In this example, the template is `articles/list`, see
[Templates and layouts](TemplatesAndLayouts.md).

`view()` accepts `content`, `template`, `layout`, `locals`, and `content_type`, which are all
optional. Without the trait you can use the provider directly:

```php
<?php

$view = $view_provider->view($this); // $this is the controller
$view->content = $articles;
$view['title'] = "Last ten articles";
$view->assign([ 'subtitle' => "Fresh from the press", 'author' => $author ]);
```

Locals are available through the array interface of the view. Reading an undefined local throws
`OffsetNotDefined`.



## Turning a view into a response

`View::into_response()` renders the view and creates a response with the content type of the view,
`text/html; charset=utf-8` by default (see [Other formats](OtherFormats.md)). The router calls it for you, but you can call it yourself, e.g. in tests:

```php
<?php

use ICanBoogie\HTTP\Request;
use ICanBoogie\Routing\Route;

$request = Request::from('/articles');
$request->context->add(new Route('/articles', 'articles:list'));

$response = $view->into_response($request);
```

A view can also be rendered to a string with `render()`, or by casting it to a string. In that case
the template must be set, because there is no route to resolve it from.



[View]: ../lib/View.php
[ViewProvider]: ../lib/ViewProvider.php
[ViewTrait]: ../lib/ViewTrait.php
[IntoResponse]: https://github.com/ICanBoogie/Routing/blob/master/lib/IntoResponse.php
[stateless controller]: https://github.com/ICanBoogie/Routing/blob/master/docs/StatelessControllers.md
[icanboogie/render]: https://github.com/ICanBoogie/Render
[icanboogie/bind-view]: https://github.com/ICanBoogie/bind-view
[ICanBoogie]: https://github.com/ICanBoogie/ICanBoogie
