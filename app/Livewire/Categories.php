<?php

namespace App\Livewire;

use App\Livewire\Forms\CategoryForm;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithPagination;
    use WithTableSorting;
    use SearchesCategoriesForSelect;

    public bool $showModalForm = false;
    public bool $showModalDelete = false;

    public CategoryForm $form;

    public ?Category $categoryToDelete = null;

    protected array $queryString = ['search'];

    /**
     * Render the paginated category list with active filters.
     */
    public function render(): View
    {
        $categories = Category::queryWithFilters(
            $this->sortBy,
            $this->sortDir,
            $this->search
        )->paginate();

        return view('livewire.categories', compact('categories'));
    }

    /**
     * Open the form modal with a clean form state.
     */
    public function openModal(): void
    {
        $this->form->resetForm();
        $this->dispatch('reset-form');
        $this->showModalForm = true;
    }

    /**
     * Load category data into the form and open the edit modal.
     */
    public function edit(Category $category): void
    {
        $this->form->resetForm();
        $this->form->setCategory($category);
        $this->showModalForm = true;

        $this->dispatch('set-property', [
            'id' => $category->parent_id,
            'name' => $category->parent?->name,
        ]);
    }

    /**
     * Validate and persist the form data.
     */
    public function save(): void
    {
        $this->form->save((int) auth()->id());
        $this->form->resetForm();
        $this->dispatch('reset-form');
        $this->showModalForm = false;
    }

    /**
     * Set the category that will be deleted.
     */
    public function confirmDelete(Category $category): void
    {
        $this->categoryToDelete = $category;
        $this->showModalDelete = true;
    }

    /**
     * Delete the selected category and close the confirmation modal.
     */
    public function delete(): void
    {
        $this->categoryToDelete?->delete();
        $this->categoryToDelete = null;
        $this->showModalDelete = false;
    }

    /**
     * Keep the slug in sync when the name changes.
     */
    public function updatedFormName(?string $value): void
    {
        $this->form->slug = Str::slug((string) $value, '-');
    }

    /**
     * Store the selected parent category id from the search component.
     */
    #[On('selected')]
    public function selectedParentCategory(array $data): void
    {
        $this->form->parent_id = isset($data['id']) ? (int) $data['id'] : null;
    }
}
