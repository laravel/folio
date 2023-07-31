<!DOCTYPE html>
<html>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @if (isset($title))
                <header class="bg-white dark:bg-gray-800 shadow">
                    My Page Title Is: {{ $title }}.
                </header>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
