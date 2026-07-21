<?php

use Livewire\Component;

new class extends Component
{
    public string $team;

    public function mount(string $team): void
    {
        $this->team = $team;
    }
};

?>

<div>Team: {{ $team }}</div>
