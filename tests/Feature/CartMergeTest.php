<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;
    private Product $productInStock;
    private string $cartToken = 'test-cart-token-merge';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->site = Site::create([
            'code'   => 'fr',
            'name'   => 'NutriSport France',
            'domain' => 'nutri-sport.fr',
        ]);

        $this->productInStock = Product::create(['name' => 'Whey Protéine', 'stock' => 50]);
        ProductPrice::create([
            'product_id' => $this->productInStock->id,
            'site_id'    => $this->site->id,
            'price'      => 29.99,
        ]);
    }

    public function test_cart_is_merged_on_login(): void
    {
        // 1. Add product to guest cart
        $this->postJson("/api/fr/cart", [
            'product_id' => $this->productInStock->id,
            'quantity'   => 2,
        ], ['X-Cart-Token' => $this->cartToken])->assertStatus(200);

        // 2. Create user
        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password',
            'site_id'  => $this->site->id,
        ]);

        // 3. Login
        $response = $this->postJson("/api/fr/login", [
            'email'    => 'test@example.com',
            'password' => 'password',
        ], ['X-Cart-Token' => $this->cartToken])->assertStatus(200);

        $token = $response->json('access_token');

        // 4. Verify user cart has the items
        $this->getJson("/api/fr/cart", [
            'Authorization' => "Bearer $token",
            'X-Cart-Token'  => $this->cartToken,
        ])
        ->assertStatus(200)
        ->assertJsonPath('items.0.product_id', $this->productInStock->id)
        ->assertJsonPath('items.0.quantite', 2);
    }
}
