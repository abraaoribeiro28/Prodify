<?php

namespace App\Livewire;

trait WithTableSorting
{
    public ?string $search = null;
    public ?string $sortBy = null;
    public ?string $sortDir = null;

    /**
     * Reset pagination when filter/sort state changes.
     */
    public function updating(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle sort direction for the selected column.
     */
    public function sort(string $column): void
    {
        $this->sortDir = ($this->sortBy === $column && $this->sortDir === 'asc')
            ? 'desc'
            : 'asc';

        $this->sortBy = $column;
    }
}
