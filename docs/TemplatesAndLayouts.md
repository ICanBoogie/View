# Templates and layouts

Views are rendered with a _template_ and a _layout_. The template renders the content of the view,
while the layout _decorates_ the result. For instance an `articles/list` template renders a
collection of articles, and a `page` layout decorates it with the layout of the website.

The names are not paths. The `Renderer` of [icanboogie/render][] resolves them with its template
resolver, and renders them with the engine matching their extension. Because templates are usually
organized as a hierarchy in an application, they can be replaced to better suit it.

All templates receive the `content` and `view` locals, along with the locals of the view.



## Template

The template is resolved when the view is turned into a response:

1. The `template` of the view, if it was set e.g. with `view(template: 'articles/overview')`.
2. The action of the route, where the separator is replaced by `/`: `articles:show` gives
   `articles/show`.



## Layout

The layout is resolved when the view is turned into a response:

1. The `layout` of the view, if it was set.
2. As decided by the `LayoutResolver`. `LayoutResolver\Basic` uses:
    - `admin` if the action of the route starts with `admin:`.
    - `home` if the pattern of the route is `/` and the layout exists.
    - `page` if the layout exists.
    - `default` otherwise.

To use another strategy, implement `LayoutResolver`, the route is its only argument.

Set `layout` to override the layout of an action:

```php
<?php

public function dashboard(): View
{
    return $this->view(content: $this->stats(), layout: 'admin');
}
```



## Decorators

A view can be decorated with additional layouts, applied in order after the layout:

```php
<?php

$view->decorate_with('sidebar');
$view->decorate_with('document');
```



## Partials

`partial()` renders a partial template with the renderer of the view. The view is passed to the
partial, as the `view` local.

```php
<?php

echo $view->partial('articles/overview', $article, [ 'show_author' => false ]);
```
