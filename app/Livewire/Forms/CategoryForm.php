<?php

namespace App\Livewire\Forms;

use App\Models\Category;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CategoryForm extends Form
{
    public bool $status = true;
    public ?string $name = null;
    public ?string $slug = null;
    public ?int $parent_id = null;
    public ?int $categoryId = null;

    /**
     * Return validation rules for category fields.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== null && $this->categoryId !== null && (int) $value === $this->categoryId) {
                        $fail('A categoria não pode ser parente de si mesma.');
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->categoryId),
            ],
            'status' => ['boolean'],
        ];
    }

    /**
     * Return custom validation messages.
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.min' => 'O campo nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O campo nome não deve ter mais de 255 caracteres.',
            'parent_id.exists' => 'A categoria parente selecionada não existe na sua conta.',
            'slug.required' => 'O campo slug é obrigatório.',
            'slug.unique' => 'Já existe uma categoria com este nome.',
        ];
    }

    /**
     * Fill form state from an existing category.
     */
    public function setCategory(Category $category): void
    {
        $this->fill($category->only([
            'name',
            'slug',
            'status',
            'parent_id',
        ]));

        $this->categoryId = $category->id;
    }

    /**
     * Validate fields and persist the category.
     */
    public function save(int $userId): void
    {
        $validated = $this->validate();

        Category::updateOrCreate(
            ['id' => $this->categoryId],
            [...$validated, 'user_id' => $userId]
        );
    }

    /**
     * Reset form fields to their default state.
     */
    public function resetForm(): void
    {
        $this->reset();
    }
}
