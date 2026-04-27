---
name: folio-routing
description: "Creates file-based routes with Laravel Folio. Activates when creating new pages, setting up routes, working with route parameters or model binding, adding middleware to pages, working with resources/views/pages; or when the user mentions Folio, pages, file-based routing, page routes, or creating a new page for a URL path."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Folio Routing

## Documentation

Use `search-docs` for detailed Folio patterns and documentation.

## Basic Usage

Laravel Folio is a file-based router that creates a new route for every Blade file within the configured directory.

Pages are usually in `resources/views/pages/` and the file structure determines routes:

- `pages/index.blade.php` → `/`
- `pages/profile/index.blade.php` → `/profile`
- `pages/auth/login.blade.php` → `/auth/login`

### Listing Routes

You may list available Folio routes using `{{ $assist->artisanCommand('folio:list') }}` or using the `list-routes` tool.

### Creating Pages

Always create new `folio` pages and routes using `{{ $assist->artisanCommand('folio:page [name]') }}` following existing naming conventions.

<!--Example folio:page Commands for Automatic Routing-->
```shell
// Creates: resources/views/pages/products.blade.php → /products
{{ $assist->artisanCommand('folio:page "products"') }}

// Creates: resources/views/pages/products/[id].blade.php → /products/{id}
{{ $assist->artisanCommand('folio:page "products/[id]"') }}

// Creates: resources/views/pages/users/[User].blade.php → /users/{user} (implicit model binding)
{{ $assist->artisanCommand('folio:page "users/[User]"') }}
```

## Page File Structure

A Folio page has up to two distinct code blocks above the Blade template:

1. Metadata block (required for `name`/`middleware`/`render`/`withTrashed`) — a raw `<?php ?>` block at the very top.
2. View-data block (optional) — a `@php @endphp` Blade directive below the metadata block, for per-request data loading.

@boostsnippet("Folio Page Skeleton", "blade")
<?php
use function Laravel\Folio\{name, middleware};

name('posts.show');
middleware(['auth']);
?>

@php
    use App\Models\Post;
    $related = Post::latest()->take(3)->get();
@endphp

<h1>{{ $post->title }}</h1>
@endboostsnippet

## Route Parameters vs. Model Binding

Use the correct filename token based on intent:

- `[id]` (lowercase) captures a plain route parameter string
- `[User]` (capitalized model class) enables implicit Eloquent model binding
- `[Post:slug]` binds by a custom key instead of `id`

Model binding is case-sensitive in the filename. Avoid `[user]` when you expect a `User` model instance.

@boostsnippet("Route Parameter vs Model Binding Example", "blade")
{{-- pages/users/[id].blade.php --}}
<div>User ID: {{ $id }}</div>

{{-- pages/users/[User].blade.php --}}
<div>User ID: {{ $user->id }}</div>
@endboostsnippet

## Named Routes

Add a `name` at the top of each new Folio page to create a named route that other parts of the codebase can reference.

@boostsnippet("Named Routes Example", "php")
<?php
use function Laravel\Folio\name;

name('products.index');
?>
@endboostsnippet

### Generating URLs to Folio Routes

Folio's URL generator requires route parameters as a keyed array. It does not auto-coerce a single Eloquent model the way Laravel's default route helper does — passing a model directly throws `TypeError: Laravel\Folio\FolioRoutes::get(): Argument #2 ($arguments) must be of type array`.

The array key must match the filename token: for `pages/posts/[Post].blade.php` the key is `post`; for `pages/posts/[Post:slug].blade.php` the key is still `post` (the `:slug` part only changes which column Folio resolves by).

@boostsnippet("Linking to a Folio Route with Model Binding", "blade")
{{-- Correct: keyed array --}}
<a href="{{ route('posts.show', ['post' => $post]) }}">{{ $post->title }}</a>

{{-- Also correct: primitive key --}}
<a href="{{ route('posts.show', ['post' => $post->id]) }}">{{ $post->title }}</a>

{{-- Wrong: throws TypeError at render time --}}
<a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
@endboostsnippet

For routes without parameters, the helper works as usual: `@{{ route('posts.index') }}`.

## Middleware

@boostsnippet("Middleware Example", "php")
<?php
use function Laravel\Folio\{name, middleware};

name('admin.products');
middleware(['auth', 'verified']);
?>
@endboostsnippet

## Page Content Patterns

Folio pages are normal Blade files. Include practical data-loading code when creating or editing pages.

@boostsnippet("Per-Request View Data (Blade @php block, below the metadata block)", "blade")
@php
use App\Models\Post;

$posts = Post::query()
    ->whereNotNull('published_at')
    ->latest('published_at')
    ->get();
@endphp

<ul>
    @foreach ($posts as $post)
        <li>{{ $post->title }}</li>
    @endforeach
</ul>
@endboostsnippet

@boostsnippet("Render Hook Example for View Data", "php")
<?php
use App\Models\Post;
use Illuminate\View\View;
use function Laravel\Folio\render;

render(function (View $view, Post $post) {
    return $view->with('photos', $post->author->photos);
});
?>
@endboostsnippet

## Verification

1. Run `{{ $assist->artisanCommand('folio:list') }}` to verify route registration
2. Test page loads at expected URL

## Common Pitfalls

- Forgetting to add named routes to new Folio pages
- Using `[id]` or `[user]` when model binding requires `[User]`
- Not following existing naming conventions when creating pages
- Creating routes manually in `routes/web.php` instead of using Folio's file-based routing
- Wrapping `name()`, `middleware()`, `render()`, or `withTrashed()` in a `@php @endphp` Blade directive. Folio's scanner only reads raw `<?php ?>` blocks, so those calls are silently ignored — the named route is never registered, middleware is never applied, and `folio:list` will not show the expected route attributes.
- Calling `route('page.name', $model)` with a single model instance. Folio's URL generator requires a keyed array: `route('page.name', ['model' => $model])`. Passing a model directly throws `TypeError` at render time.

### Folio 404 Debug Checklist

1. Run `{{ $assist->artisanCommand('folio:list') }}`
2. If routes are missing, confirm Folio is mounted (`folio:install` + provider registration, or `Folio::path(...)`) and pages are under the mounted path
3. Verify filename-to-route mapping (`index.blade.php`, nested paths, `[id]` vs `[Model]`), then rerun `{{ $assist->artisanCommand('folio:list') }}`
