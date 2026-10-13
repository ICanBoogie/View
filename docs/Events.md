# Events

## `ICanBoogie\View\View\AlterEvent`

Emitted when a view is turned into a response, after its template and layout were resolved. Event
hooks may use it to alter the view.

The sender of the event is the controller that created the view, so hooks can target specific
controllers. The event provides the `view`, and the `Request`, whose context has the route of the
request, e.g. to adjust the view according to the action.

```php
<?php

use App\Articles\ArticleController;
use ICanBoogie\Routing\Route;
use ICanBoogie\View\View;

$app->events->attach(function (View\AlterEvent $event, ArticleController $controller) {
    $route = $event->request->context->get(Route::class);

    $event->view['action'] = $route->action;
    $event->view->layout = 'blog';
});
```

The hook above is only invoked for views created by `ArticleController`, and by its subclasses. To
alter the views of several controllers, type the parameter with their common parent class. Interfaces
are not considered.

The type of the sender is what matches the hook. If the second parameter is omitted or typed as
`object`, the hook is never invoked.
