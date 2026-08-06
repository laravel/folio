<?php

use Livewire\Component;

new class extends Component
{
    public string $heading = 'Livewire home';
};

?>

<div>{{ $heading }}</div>
