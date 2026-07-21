<?php

use Livewire\Component;

new class extends Component
{
    public string $heading = 'Livewire profile';
};

Laravel\Folio\name('livewire.profile');

?>

<div>{{ $heading }}</div>
