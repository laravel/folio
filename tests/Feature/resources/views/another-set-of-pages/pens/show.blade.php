<x-app>
    <ul>
        <li>Is pens.show active: {{ request()->routeIs('pens.show') ? 'true' : 'false' }}.</li>
        <li>Current route name: {{ Route::currentRouteName() }}.</li>
        <li>Has pens.show: {{ Route::has('pens.show') ? 'true' : 'false' }}.</li> <!-- It's a know limitation... -->
        <li>Is pens/show: {{ request()->is('pens/show') ? 'true' : 'false' }}.</li>
    </ul>
</x-app>
