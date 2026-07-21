<?php

use Livewire\Component;

new class extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }
};

?>

<div>Profile: {{ $slug }}</div>
