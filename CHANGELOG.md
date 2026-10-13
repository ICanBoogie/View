# CHANGELOG

## v7.0

### New Requirements

PHP 8.4+

### New features

- `View` implements `IntoResponse`, it can be returned by actions of stateless controllers. `View::$content_type` (and the `content_type` argument of `RenderTrait::view()`) sets the content type of the response, `text/html; charset=utf-8` by default. The template and layout are resolved from the request's route when the view is turned into a response.

### Deprecated Features

None

### Backward Incompatible Changes

- `ViewProvider::view_for_controller(ControllerAbstract)` is replaced by `ViewProvider::view(object $controller)`, any controller is supported.
- `View::$template` is nullable until the view is turned into a response.
- `View\AlterEvent` is sent by the controller instead of the view, which is available as `$event->view`. The event also provides the `Request`, and is emitted when the view is turned into a response.
- `RenderTrait` no longer requires `ControllerAbstract`, and `render()` and `render_to_string()` are removed, return `view()` from the action instead.

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
