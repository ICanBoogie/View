# View

[![Release](https://img.shields.io/packagist/v/icanboogie/view.svg)](https://packagist.org/packages/icanboogie/view)
[![Code Coverage](https://coveralls.io/repos/github/ICanBoogie/View/badge.svg?branch=6.0)](https://coveralls.io/r/ICanBoogie/View?branch=6.0)
[![Downloads](https://img.shields.io/packagist/dt/icanboogie/view.svg)](https://packagist.org/packages/icanboogie/view)

The **icanboogie/view** package provides the _view_ part of the model-view-controller (MVC)
architectural pattern. It extends the features of the [icanboogie/routing][] package—more precisely its
controllers—and together with the [icanboogie/render][] package it helps in separating
presentation from logic.



#### Installation

```shell
composer require icanboogie/view
```



## Usage

Actions of [stateless controllers][] return a view, the template and layout are resolved from the
route when the view is turned into a response.

```php
<?php

namespace App\Articles;

use ICanBoogie\Routing\Attribute\Get;
use ICanBoogie\View\RenderTrait;
use ICanBoogie\View\View;
use ICanBoogie\View\ViewProvider;

final class ArticleController
{
    use RenderTrait;

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

Read the [documentation](docs/README.md) to learn how to provide views, how templates and layouts
are resolved, and how to alter views with events.



----------



## Continuous Integration

The project is continuously tested by [GitHub actions](https://github.com/ICanBoogie/View/actions).

[![Tests](https://github.com/ICanBoogie/View/actions/workflows/test.yml/badge.svg?branch=6.0)](https://github.com/ICanBoogie/View/actions/workflows/test.yml)
[![Static Analysis](https://github.com/ICanBoogie/View/actions/workflows/static-analysis.yml/badge.svg?branch=6.0)](https://github.com/ICanBoogie/View/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/ICanBoogie/View/actions/workflows/code-style.yml/badge.svg?branch=6.0)](https://github.com/ICanBoogie/View/actions/workflows/code-style.yml)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you're expected to uphold this code.



## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.



[stateless controllers]: https://github.com/ICanBoogie/Routing/blob/master/docs/StatelessControllers.md
[icanboogie/render]:      https://github.com/ICanBoogie/Render
[icanboogie/routing]:     https://github.com/ICanBoogie/Routing
