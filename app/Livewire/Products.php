<?php

namespace App\Livewire;

use App\Livewire\Forms\ProductForm;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;
    use WithFileUploads;
    use WithTableSorting;
    use SearchesCategoriesForSelect;

    public bool $showModalForm = false;
    public bool $showModalDelete = false;

    public ProductForm $form;
    public ?Product $productToDelete = null;

    /**
     * Render the paginated product list with active filters.
     */
    public function render(): View
    {
        $products = Product::queryWithFilters(
            $this->sortBy,
            $this->sortDir,
            $this->search
        )->paginate();

        return view('livewire.products', compact('products'));
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
     * Load product data into the form and open the edit modal.
     */
    public function edit(Product $product): void
    {
        $this->form->resetForm();
        $this->form->setProduct($product);
        $this->showModalForm = true;

        $this->dispatch('set-property', [
            'id' => $product->category_id,
            'name' => $product->category?->name,
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
     * Remove one pending image from the upload queue.
     */
    public function removeImage(int $index): void
    {
        $this->form->removeImage($index);
    }

    /**
     * Set the product that will be deleted.
     */
    public function confirmDelete(Product $product): void
    {
        $this->productToDelete = $product;
        $this->showModalDelete = true;
    }

    /**
     * Delete the selected product and close the confirmation modal.
     */
    public function delete(): void
    {
        $this->productToDelete?->delete();
        $this->productToDelete = null;
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
     * Store the selected category id from the search component.
     */
    #[On('selected')]
    public function selectedCategory(array $data): void
    {
        $this->form->category_id = isset($data['id']) ? (int) $data['id'] : null;
    }
}
