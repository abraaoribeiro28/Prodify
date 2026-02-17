<?php

namespace App\Livewire\Forms;

use App\Models\Archive;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProductForm extends Form
{
    public bool $status = true;
    public ?string $name = null;
    public ?string $slug = null;
    public ?string $price = null;
    public ?int $stock = 0;
    public ?string $description = null;
    public ?int $category_id = null;
    public array $images = [];
    public array $existingImages = [];
    public ?int $productId = null;

    /**
     * Return validation rules for product fields.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'slug' => [
                'required',
                'string',
                'max:150',
                Rule::unique('products', 'slug')->ignore($this->productId),
            ],
            'price' => ['required', 'string'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['boolean'],
            'description' => ['nullable', 'string', 'max:999'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:2048'],
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
            'name.max' => 'O campo nome não deve ter mais de 150 caracteres.',
            'category_id.required' => 'Selecione uma categoria.',
            'category_id.exists' => 'A categoria selecionada não existe na sua conta.',
            'slug.required' => 'O campo slug é obrigatório.',
            'slug.unique' => 'Já existe um produto com este nome.',
            'price.required' => 'O campo preço é obrigatório.',
            'stock.required' => 'O campo estoque é obrigatório.',
            'stock.integer' => 'O campo estoque deve ser um número inteiro.',
            'description.max' => 'A descrição não deve ter mais de 999 caracteres.',
            'images.max' => 'Você pode enviar no máximo 5 imagens por produto.',
            'images.*.image' => 'Cada arquivo precisa ser uma imagem.',
            'images.*.max' => 'Cada imagem deve ter no máximo 2 MB.',
        ];
    }

    /**
     * Fill form state from an existing product.
     */
    public function setProduct(Product $product): void
    {
        $product->loadMissing('archives', 'category');

        $this->fill($product->only([
            'name',
            'slug',
            'stock',
            'status',
            'description',
            'category_id',
        ]));

        $this->productId = $product->id;
        $this->price = 'R$ ' . number_format((float) $product->price, 2, ',', '.');
        $this->existingImages = $product->archives->map(static function (Archive $archive): array {
            $fallbackPath = "/images/products/{$archive->archive}";
            $storagePath = "products/{$archive->archive}";
            $url = $archive->path
                ?: (Storage::disk('public')->exists($storagePath)
                    ? Storage::url($storagePath)
                    : $fallbackPath);

            return [
                'id' => $archive->id,
                'url' => $url,
                'name' => $archive->filename ?? $archive->archive,
            ];
        })->toArray();
    }

    /**
     * Validate fields, save the product, and persist uploads.
     */
    public function save(int $userId): void
    {
        $validated = $this->validate();
        $validated['price'] = $this->sanitizePrice($validated['price']);

        unset($validated['images']);

        $product = Product::updateOrCreate(
            ['id' => $this->productId],
            [...$validated, 'user_id' => $userId]
        );

        $this->persistImages($product);
    }

    /**
     * Remove one image from the pending upload list.
     */
    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    /**
     * Reset form fields to their default state.
     */
    public function resetForm(): void
    {
        $this->reset();
    }

    /**
     * Store uploaded images and attach them to the product.
     */
    private function persistImages(Product $product): void
    {
        if ($this->images === []) {
            return;
        }

        $archiveIds = [];

        foreach ($this->images as $image) {
            if (!$image instanceof TemporaryUploadedFile) {
                continue;
            }

            $fileName = Str::uuid()->toString() . '.' . $image->getClientOriginalExtension();
            $storedPath = $image->storeAs('products', $fileName, 'public');

            $archive = Archive::create([
                'filename' => pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $image->getClientOriginalExtension(),
                'archive' => $fileName,
                'path' => Storage::url($storedPath),
            ]);

            $archiveIds[] = $archive->id;
        }

        if ($archiveIds !== []) {
            $product->archives()->syncWithoutDetaching($archiveIds);
        }
    }

    /**
     * Normalize a formatted price string to decimal format.
     */
    private function sanitizePrice(string $price): string
    {
        $raw = trim($price);
        $raw = preg_replace('/[^\d,.\-]/', '', $raw) ?? '';

        if ($raw === '' || $raw === '-' || $raw === ',' || $raw === '.') {
            throw ValidationException::withMessages([
                'price' => 'Informe um preço válido.',
            ]);
        }

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $raw);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $raw);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace('.', '', $raw);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = $raw;

            if (substr_count($normalized, '.') > 1) {
                $normalized = str_replace('.', '', $normalized);
            }
        }

        if (!is_numeric($normalized) || (float) $normalized < 0) {
            throw ValidationException::withMessages([
                'price' => 'Informe um preço válido.',
            ]);
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}
