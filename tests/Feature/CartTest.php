<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;
    private Product $productInStock;
    private Product $productOutOfStock;

    /** Token injected as X-Cart-Token to keep cart keys stable across requests */
    private string $cartToken = 'test-cart-token-stable';

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

        $this->productOutOfStock = Product::create(['name' => 'Créatine', 'stock' => 0]);
        ProductPrice::create([
            'product_id' => $this->productOutOfStock->id,
            'site_id'    => $this->site->id,
            'price'      => 19.99,
        ]);
    }

    private function cartHeaders(): array
    {
        return ['X-Cart-Token' => $this->cartToken];
    }

    public function test_can_view_empty_cart(): void
    {
        $this->getJson("/api/fr/cart", $this->cartHeaders())
             ->assertStatus(200)
             ->assertJsonStructure(['items', 'total'])
             ->assertJson(['total' => 0, 'items' => []]);
    }

    public function test_can_add_product_to_cart(): void
    {
        $this->postJson("/api/fr/cart", [
            'product_id' => $this->productInStock->id,
            'quantity'   => 2,
        ], $this->cartHeaders())
        ->assertStatus(200)
        ->assertJsonPath('cart.items.0.product_id', $this->productInStock->id)
        ->assertJsonPath('cart.items.0.quantite', 2)
        ->assertJsonPath('cart.total', 59.98);
    }

    public function test_adding_same_product_accumulates_quantity(): void
    {
        $this->postJson("/api/fr/cart", ['product_id' => $this->productInStock->id, 'quantity' => 1], $this->cartHeaders());
        $this->postJson("/api/fr/cart", ['product_id' => $this->productInStock->id, 'quantity' => 3], $this->cartHeaders())
             ->assertStatus(200)
             ->assertJsonPath('cart.items.0.quantite', 4);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $this->postJson("/api/fr/cart", [
            'product_id' => $this->productOutOfStock->id,
            'quantity'   => 1,
        ], $this->cartHeaders())
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Produit en rupture de stock.']);
    }

    public function test_can_remove_product_from_cart(): void
    {
        $this->postJson("/api/fr/cart", ['product_id' => $this->productInStock->id, 'quantity' => 2], $this->cartHeaders());

        $this->withHeaders($this->cartHeaders())
             ->deleteJson("/api/fr/cart/{$this->productInStock->id}")
             ->assertStatus(200)
             ->assertJsonFragment(['message' => 'Produit retiré du panier.']);

        $this->getJson("/api/fr/cart", $this->cartHeaders())
             ->assertStatus(200)
             ->assertJsonPath('items', [])
             ->assertJsonPath('total', 0);
    }

    public function test_can_clear_cart(): void
    {
        $this->postJson("/api/fr/cart", ['product_id' => $this->productInStock->id, 'quantity' => 1], $this->cartHeaders());

        $this->deleteJson("/api/fr/cart", [], $this->cartHeaders())
             ->assertStatus(200)
             ->assertJsonFragment(['message' => 'Panier vidé.']);

        $this->getJson("/api/fr/cart", $this->cartHeaders())
             ->assertJsonPath('total', 0)
             ->assertJsonPath('items', []);
    }

    public function test_add_to_cart_validates_product_id(): void
    {
        $this->postJson("/api/fr/cart", ['product_id' => 99999, 'quantity' => 1], $this->cartHeaders())
             ->assertStatus(422);
    }

    public function test_add_to_cart_validates_positive_quantity(): void
    {
        $this->postJson("/api/fr/cart", [
            'product_id' => $this->productInStock->id,
            'quantity'   => 0,
        ], $this->cartHeaders())
        ->assertStatus(422);
    }

    public function test_cart_persists_in_cache(): void
    {
        $this->postJson("/api/fr/cart", ['product_id' => $this->productInStock->id, 'quantity' => 3], $this->cartHeaders());

        $this->getJson("/api/fr/cart", $this->cartHeaders())
             ->assertStatus(200)
             ->assertJsonPath('items.0.quantite', 3);
    }

    public function test_cart_does_not_require_authentication(): void
    {
        $this->getJson("/api/fr/cart", $this->cartHeaders())
             ->assertStatus(200);
    }
}
