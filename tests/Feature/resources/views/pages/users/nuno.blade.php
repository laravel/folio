@php
$title = 'Nuno Maduro';

$renderedCount = $_SERVER['__folio_rendered_count'] = ($_SERVER['__folio_rendered_count'] ?? 0) + 1;
@endphp

<div>Hello, {{ $title }} from PHP block.</div>
<div>Rendered [{{ $renderedCount }}] time from PHP block.</div>
