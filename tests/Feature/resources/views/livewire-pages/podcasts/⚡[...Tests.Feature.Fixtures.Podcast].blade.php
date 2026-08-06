<?php

use Livewire\Component;

new class extends Component
{
    public $podcasts;

    public function mount($podcasts): void
    {
        $this->podcasts = $podcasts;
    }
};

?>

<div>{{ collect($podcasts)->pluck('name')->join(', ') }}</div>
