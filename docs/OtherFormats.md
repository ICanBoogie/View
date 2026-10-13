# Other formats

Views render HTML by default, the response has the `text/html; charset=utf-8` content type. Use
`content_type` to render something else, such as an RSS feed:

```php
<?php

use ICanBoogie\Routing\Attribute\Get;
use ICanBoogie\View\View;

#[Get('/articles.rss')]
public function feed(): View
{
    return $this->view(
        content: $this->articles->latest(10),
        template: 'articles/feed',
        layout: 'feed',
        content_type: 'application/rss+xml; charset=utf-8',
    );
}
```

The content type can also be set on the view, `$view->content_type = 'application/rss+xml'`.

For JSON, a view isn't needed, return a response directly:

```php
<?php

use ICanBoogie\HTTP\Response;
use ICanBoogie\Routing\Attribute\Get;

#[Get('/articles.json')]
public function list_json(): Response
{
    return new Response(
        json_encode($this->articles->latest(10)),
        headers: [ 'Content-Type' => 'application/json' ],
    );
}
```

A view is `JsonSerializable`, it serializes its content, template, layout, and locals. It is
useful for debugging, or as a cache key.
