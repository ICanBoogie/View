# View

[![Release](https://img.shields.io/packagist/v/icanboogie/view.svg)](https://packagist.org/packages/icanboogie/view)
[![Build Status](https://img.shields.io/travis/ICanBoogie/View/master.svg)](http://travis-ci.org/ICanBoogie/View)
[![HHVM](https://img.shields.io/hhvm/icanboogie/view.svg)](http://hhvm.h4cc.de/package/icanboogie/view)
[![Code Quality](https://img.shields.io/scrutinizer/g/ICanBoogie/View/master.svg)](https://scrutinizer-ci.com/g/ICanBoogie/View)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/View/master.svg)](https://coveralls.io/r/ICanBoogie/View)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/view.svg)](https://packagist.org/packages/icanboogie/view)

The **icanboogie/view** package provides the _view_ part of the model-view-controller (MVC)
architectural pattern. It extends the features of the Routing package, more precisely its
controllers, and together with the [icanboogie/render][] package it helps in separating
presentation from logic.

The package [icanboogie/render][] is used to locate templates and render them with the appropriate
engines.





## Getting started

Before you get started you'll need to define some prototype methods to bind some _render_
components to [View][] instances, a [View][] instances to the [Controller][] instance that use
them.

If you use the **icanboogie/view** package with [ICanBoogie][] you can simply require the
[icanboogie/bind-view][] and let it deal with the bindings.

The following code demonstrates how to bind the `engines` and `template_resolver` properties
of [View][] instances, as well the `view` property of [Controller][] instances.

```php
<?php

use ICanBoogie\Prototype;
use ICanBoogie\Render;
use ICanBoogie\Routing\Controller;
use ICanBoogie\View;

Prototype::configure([

	'ICanBoogie\Routing\Controller' => [

		'lazy_get_view' => function(Controller $controller) {

			$view = new View($controller);

			new View\AlterEvent($view);

			return $view;

		}

	],

	'ICanBoogie\View\View' => [

		'lazy_get_engines' => function(){

			return Render\get_engines();

		},

		'lazy_get_template_resolver' => function() {

			return clone Render\get_template_resolver();

		}
	]

]);
```





## Views and controllers

Views are associated with controllers through the lazy getter `view`, thus a simple `$this->view`
is all that is required to enable view features inside a controller. The view then waits for
the `Controller::action` event to perform its rendering.

The following example demonstrates how a query of some articles is set as the view content,
a title is also added to the view variables:

```php
<?php

use ICanBoogie\Routing\ActionController;

class ArticlesController extends ActionController
{
	protected function action_any_index()
	{
		$this->view->content = $this->model->visible->ordered->limit(10);
		$this->view['title'] = "Last ten articles";
	}
}
```

**Note:** The `model` getter is provided by the [icanboogie/module][] package, and is only
available if the route has a `module` property, which is automatic for routes defined by modules.





### Altering the view before it is returned to the controller

The event `View::alter` of class [View\AlterEvent][] is fired when the instance is
created by the `view` getter. Event hooks may used this event to alter the view before it is
returned to the controller.

The following example demonstrates how a view can be altered before it is returned to the
controller. If the route has a `module` property, the "template" directory of the module is
added to the template resolver:

```php
<?php

use ICanBoogie\PropertyNotDefined;
use ICanBoogie\View\View;

$app->events->attach(function(View\AlterEvent $event, View $view) use ($app) {

	try
	{
		$module_id = $view->controller->route->module;
	}
	catch (PropertyNotDefined $e)
	{
		// if the property is not defined we just return

		return;
	}

	$view->template_resolver->add_path($app->modules[$module_id]->path . 'templates');

});
```





## Rendering a view

Views are rendered using __templates__ and __layouts__. Templates render the content of views,
while layouts _decorate_ the templates. For instance an "articles/list" template would be used to
render a collection of articles, while a "page" layout would be used to decorate that rendered
collection with the layout of the website.

The template used to present the content of the view is resolved as follows:

- From the `template` property of the view.
- From the `template` property of the route.
- From the `template` property of the controller.
- If the controller is an [ActionController][], from its name and action e.g. "articles/show".

The layout used to decorate the template is resolved as follows:

- From the `layout` property of the view.
- From the `layout` property of the route.
- From the `layout` property of the controller.
- "admin" if the identifier of the route starts with "admin:".
- "home" if the pattern of the route is "/" and the template exists.
- "page" if the template exists.
- "default" otherwise.

Because the `template` and `layout` properties are lazily created, you can define them instead of
letting View find the right template names. The following example demonstrates how to _cancel_ the
template and define "admin" as layout:

```php
<?php

	// …

	protected function action_any_index()
	{
		$this->view->content = $this->model->visible->ordered->limit(10);
		$this->view->template = null;
		$this->view->layout = "admin";
	}

	// …
```

The templates and layouts are usually specified as _names_ e.g. "page" or "articles/show", and not
by path e.g. "/path/to/my/template.php". A template resolver and an engine collection are used
to resolve these names into pathname, and the engine collection is used to render the templates
with the appropriate engines. The reason for this is that templates are usually defined as a
hierarchy in your application, and using this hierarchy they can be replaced to better suit
your application.

For instance, the framework [ICanBoogie][] decorates the default template resolver to add additional
features, and also to add the application directories to the template resolver.

Please take a look at the [icanboogie/render][] package for more details about template resolvers
and engine collections.





### Altering a view before it is rendered

The event `View::render:before` of class [View\BeforeRenderEvent][] is fired before the
view is rendered. Event hooks may use this event to alter the view and override properties or
variables set by the controller.

The following example demonstrates how to add variables to a view before it is rendered, and
also change its layout to match the context of the application:

```php
<?php

use ICanBoogie\View\View;

$app->events->attach(function(View\BeforeRenderEvent $event, View $view) use ($app) {

	$view['alerts'] = $app->alerts;
	$view['user'] = $app->user;

	if ($app->is_mobile)
	{
		$view->layout .= '.mobile';
	}

});
```





## Rendering JSON and stuff

Views are often used to render HTML, but they can also render JSON, XML and other nice things,
and it's rather simple since all you have to do is alter the [Response][] instance of your
controller according to what you are rendering. This is not really a View feature, but its
something to remember.

The following example demonstrates how the response is altered to suit the JSON response:

```php
<?php

// templates/json.php

echo json_encode($content)
```


```php
<?php

	// …

	protected function action_any_json()
	{
		$this->view->content = $this->model->one;
		$this->view->template = 'json';
		$this->response->content_type = "application/json";
	}

	// …
```





## Cancelling a view

A view can be _cancelled_ when you need to return a different result or when you want to cancel
its rendering. Views are automatically cancelled when the controller they are attached to returns
a result. A view can also be cancelled by setting the `view` property of its controller to `null`.

The following example demonstrates how views can be cancelled using these methods:

```php
<?php

use ICanBoogie\Routing\ActionController;

class ArticlesController extends ActionController
{
	protected function action_any_index()
	{
		$this->view->content = $this->model->visible->ordered->limit(10);
		$this->view['title'] = "Last ten articles";
	}

	protected function action_any_json()
	{
		$this->action_any_index();
		$this->response->content_type = "application/json";
		// The view is cancelled to return JSON text
		return json_encode($this->view->content);
	}

	protected function action_head_index()
	{
		$this->action_any_index();
		// The view is cancelled although no result is returned
		$this->view = null;
	}
}
```





## Prototype methods

- `ICanBoogie\Routing\Controller::lazy_get_view`: Returns the [View][] instance associated with
the controller and also starts the view _magic_.

- `ICanBoogie\View\View::lazy_get_engines`: Returns the engine collection available to parse the
templates. By default, the instance is obtained using `ICanBoogie\Render\Hooks::lazy_get_engines`.

- `ICanBoogie\View\View::lazy_get_template_resolver`:  Returns the template resolver. By default,
the instance is obtained using `ICanBoogie\Render\Hooks::lazy_get_template_resolver`.





## Events

- `ICanBoogie\View\View::alter` of class [View\AlterEvent][]: fired when the instance is
created by the `Controller::view` getter. Event hooks may used this event to alter the view before
it is returned to the controller.

- `ICanBoogie\View\View::render:before` of class [View\BeforeRenderEvent][]: fired before the
view is rendered. Event hooks may use this event to alter the view before it is rendered.





----------





## Requirements

The package requires PHP 5.4 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/):

```
$ composer require icanboogie/view
```

The following package is required, you might want to check it out:

* [icanboogie/render](https://packagist.org/packages/icanboogie/render)
* [icanboogie/routing](https://packagist.org/packages/icanboogie/routing)





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/View), its repository can be
cloned with the following command line:

	$ git clone https://github.com/ICanBoogie/View.git





## Documentation

The package is documented as part of the [ICanBoogie][] framework
[documentation](http://api.icanboogie.org/view/). You can generate the documentation for the package and its dependencies with the `make doc` command. The documentation is generated in the `build/docs` directory. [ApiGen](http://apigen.org/) is required. The directory can later be cleaned with the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [PHPUnit](https://phpunit.de/) and [Composer](http://getcomposer.org/) need to be globally available to run the suite. The command installs dependencies as required. The `make test-coverage` command runs test suite and also creates an HTML coverage report in `build/coverage`. The directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://img.shields.io/travis/ICanBoogie/View/master.svg)](https://travis-ci.org/ICanBoogie/View)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/View/master.svg)](https://coveralls.io/r/ICanBoogie/View)





## License

**icanboogie/view** is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[icanboogie/bind-view]: https://github.com/ICanBoogie/bind-view
[icanboogie/module]: https://github.com/ICanBoogie/Module
[icanboogie/render]: https://github.com/ICanBoogie/Render
[icybee/patron-view-support]: https://github.com/Icybee/PatronViewSupport 
[ActionController]: http://api.icanboogie.org/routing/class-ICanBoogie.Routing.ActionController.html
[Controller]: http://api.icanboogie.org/routing/class-ICanBoogie.Routing.Controller.html
[ICanBoogie]: https://github.com/ICanBoogie/ICanBoogie
[Response]: http://api.icanboogie.org/http/class-ICanBoogie.HTTP.Response.html
[View]: http://api.icanboogie.org/view/class-ICanBoogie.View.View.html
[View\BeforeRenderEvent]: http://api.icanboogie.org/view/class-ICanBoogie.View.View.BeforeRenderEvent.html
[View\AlterEvent]: http://api.icanboogie.org/view/class-ICanBoogie.View.View.AlterEvent.html
