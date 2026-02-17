<?php

namespace Tests\Feature;

use App\Livewire\Categories;
use App\Livewire\Products;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireCrudRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_is_generated_when_form_name_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Categories::class)
            ->set('form.name', 'Categoria Principal')
            ->assertSet('form.slug', 'categoria-principal');
    }

    public function test_category_form_rejects_parent_from_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user);

        Livewire::test(Categories::class)
            ->set('form.name', 'Minha Categoria')
            ->set('form.slug', 'minha-categoria')
            ->set('form.parent_id', $otherCategory->id)
            ->call('save')
            ->assertHasErrors(['form.parent_id' => 'exists']);
    }

    public function test_category_form_rejects_self_parent_relationship(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'parent_id' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(Categories::class)
            ->call('edit', $category)
            ->set('form.parent_id', $category->id)
            ->call('save')
            ->assertHasErrors(['form.parent_id']);
    }

    public function test_product_slug_is_generated_when_form_name_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Products::class)
            ->set('form.name', 'Notebook Gamer')
            ->assertSet('form.slug', 'notebook-gamer');
    }

    public function test_product_form_saves_with_currency_masked_price(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(Products::class)
            ->set('form.name', 'Notebook Gamer')
            ->set('form.slug', 'notebook-gamer')
            ->set('form.price', 'R$ 1.234,56')
            ->set('form.stock', 5)
            ->set('form.category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Notebook Gamer',
            'slug' => 'notebook-gamer',
            'price' => '1234.56',
            'stock' => 5,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_product_form_rejects_category_from_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user);

        Livewire::test(Products::class)
            ->set('form.name', 'Produto')
            ->set('form.slug', 'produto')
            ->set('form.price', '100,00')
            ->set('form.stock', 10)
            ->set('form.category_id', $otherCategory->id)
            ->call('save')
            ->assertHasErrors(['form.category_id' => 'exists']);
    }
}
