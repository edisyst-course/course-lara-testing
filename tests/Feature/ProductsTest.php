<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    private function createUser(bool $isAdmin = false): User
    {
        return User::factory()->create([
            'is_admin' => $isAdmin
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->admin = $this->createUser(isAdmin: true);
    }

    public function test_homepage_contains_empty_table()
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table()
    {
        $product = Product::create([
            'name' => 'Product 1',
            'price' => 123
        ]);

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee(__('No products found'));
        $response->assertSee('Product 1');
        $response->assertViewHas('products', function ($collection) use ($product) {
            return $collection->contains($product);
        });
    }

    public function test_homepage_contains_table_product()
    {
        $product = Product::create([
            'name' => 'table',
            'price' => 123
        ]);
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    public function test_homepage_contains_products_in_order()
    {
        [$product1, $product2] = Product::factory(2)->create();
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertSeeInOrder([$product1->name, $product2->name]);
    }

    public function test_paginated_products_table_doesnt_contain_11th_record()
    {
        $products = Product::factory(11)->create();
//         dd($products);
        $lastProduct = $products->last();

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertViewHas('products', function ($collection) use ($lastProduct) {
            return !$collection->contains($lastProduct);
        });
    }

    public function test_admin_can_see_products_create_button()
    {
        $response = $this->actingAs($this->admin)->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Add new product');
    }

    public function test_non_admin_cannot_see_products_create_button()
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee('Add new product');
    }

    public function test_admin_can_access_product_create_page()
    {
        $response = $this->actingAs($this->admin)->get('/products/create');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_product_create_page()
    {
        $response = $this->actingAs($this->user)->get('/products/create');

        $response->assertStatus(403);
    }

    public function test_create_product_successful()
    {
        $product = [
            'name' => 'Product 123',
            'price' => 1234
        ];
        $response = $this->actingAs($this->admin)->post('/products', $product);

        $response->assertStatus(302); // redirect (permanente)
        $response->assertRedirect('products');

        $this->assertDatabaseHas('products', $product); // verifica se la riga è presente nella tabella

        $lastProduct = Product::latest()->first(); // verifica che quella riga sia anche l'ultima inserita
        $this->assertEquals($product['name'], $lastProduct->name);
        $this->assertEquals($product['price'], $lastProduct->price);
    }

    public function test_product_edit_contains_correct_values()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->get('products/' . $product->id . '/edit');

        $response->assertStatus(200);
        $response->assertSee('value="' . $product->name . '"', false); // false serve per l'escape degli apici
        $response->assertSee('value="' . $product->price . '"', false);
        $response->assertViewHas('product', $product);
    }

    public function test_product_update_validation_error_redirects_back_to_form()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->put('products/' . $product->id, [
            'name' => '',
            'price' => ''
        ]);

        $response->assertStatus(302);
        $response->assertInvalid(['name', 'price']); // rileva proprio gli errori di validazione
    }

    public function test_product_delete_successful()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete('products/' . $product->id);

        $response->assertStatus(302);
        $response->assertRedirect('products');

        $this->assertDatabaseMissing('products', $product->toArray());
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_returns_products_list()
    {
        $product = Product::factory()->create();
        $response = $this->getJson('/api/products');

        $response->assertJson([$product->toArray()]); // le [] perchè si aspetta un array
    }

    public function test_api_product_store_successful()
    {
        $product = [
            'name' => 'Product 1',
            'price' => 123
        ];
        $response = $this->postJson('/api/products', $product);

        $response->assertStatus(201);
        $response->assertJson($product);
    }

    public function test_api_product_invalid_store_returns_error()
    {
        $product = [
            'name' => '',
            'price' => 123
        ];
        $response = $this->postJson('/api/products', $product);

        $response->assertStatus(422);
    }
}
