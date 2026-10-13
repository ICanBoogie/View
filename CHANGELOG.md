# CHANGELOG

## v7.0

### New Requirements

PHP 8.4+

### New features

- `View` implements `IntoResponse`, it can be returned by actions of stateless controllers. The router turns it into a response.
- The template and layout of a view are resolved from the route of the request when the view is turned into a response, unless they were set.
- `View::$content_type` sets the content type of the response, `text/html; charset=utf-8` by default. `ViewTrait::view()` accepts a `content_type` argument.
- `View\AlterEvent` is sent by the controller that created the view, so that hooks can target specific controllers.
- Added documentation in `docs/`.

### Deprecated Features

None

### Backward Incompatible Changes

- Requires the stateless controllers of `icanboogie/routing`.
- `RenderTrait` is renamed `ViewTrait`. It no longer requires `ControllerAbstract`, and `render()` and `render_to_string()` are removed, return `view()` from the action instead.
- `ViewProvider::view_for_controller(ControllerAbstract)` is replaced by `ViewProvider::view(object $controller)`, any controller is supported.
- `View::$template` is nullable until the view is turned into a response.
- `View\AlterEvent` is sent by the controller instead of the view, the view is available as `$event->view`. The event also provides the `Request`, and is emitted when the view is turned into a response.

### Other changes

None



## v5.x to v6.0

### New Requirements

PHP 8.2+

### New features

- Added `RenderTrait` to use with `ControllerAbstract`.

### Backward Incompatible Changes

None

### Deprecated Features

None

### Other Changes

None
