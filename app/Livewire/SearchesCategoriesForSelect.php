<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Attributes\On;

trait SearchesCategoriesForSelect
{
    /**
     * Search categories for the select component and dispatch options.
     */
    #[On('searching')]
    public function searchCategory(string $search): void
    {
        $categories = Category::query()
            ->where('user_id', auth()->id())
            ->where('name', 'like', "%{$search}%")
            ->limit(10)
            ->pluck('name', 'id')
            ->toArray();

        $this->dispatch('search-response', $categories);
    }
}
