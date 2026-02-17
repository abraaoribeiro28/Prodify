<?php

namespace App\Livewire\Components;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class SelectSearch extends Component
{
    public array $data = [];
    public string $search = '';
    public string $label = '';
    public string $placeholder = 'Selecione...';
    public ?string $selectedName = null;
    public ?int $selectedId = null;

    /**
     * Render the Livewire component view
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.components.select-search');
    }

    /**
     * Triggered when the search input is updated.
     * Dispatches 'searching' event if more than 2 characters.
     *
     * @return void
     */
    public function updatedSearch(): void
    {
        $search = trim($this->search);

        if (Str::length($search) > 2) {
            $this->dispatch('searching', $search);
        } else {
            $this->data = [];
        }
    }

    /**
     * Triggered when the selected ID is updated.
     * Dispatches a 'selected' event with the ID and name.
     *
     * @return void
     */
    public function updatedSelectedId(): void
    {
        $this->dispatch('selected', [
            'id' => $this->selectedId,
            'name' => $this->selectedName,
        ]);
    }

    /**
     * Handles external response from a 'search-response' event
     *
     * @param array $data
     * @return void
     */
    #[On('search-response')]
    public function response(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Resets component state when 'reset-form' event is received
     *
     * @return void
     */
    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->resetExcept(['label', 'placeholder']);
    }

    /**
     * Sets selected data via 'set-property' event
     *
     * @param array{id: int, name: string} $data
     * @return void
     */
    #[On('set-property')]
    public function setProperty(array $data): void
    {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;

        if ($id === null || $name === null) {
            $this->selectedId = null;
            $this->selectedName = null;
            $this->data = [];

            return;
        }

        $this->selectedId = (int) $id;
        $this->selectedName = (string) $name;
        $this->data = [$this->selectedId => $this->selectedName];
    }
}
