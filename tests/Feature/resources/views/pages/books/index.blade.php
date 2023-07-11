@php
    use Illuminate\Support\Facades\Gate;

    if (! Gate::check('view-books')) {
        abort(403);
    }

    $user = auth()->user();

    $books = $user->books;
@endphp

@foreach ($books as $book)
    <div>
        {{ $book->title }}
    </div>
@endforeach
