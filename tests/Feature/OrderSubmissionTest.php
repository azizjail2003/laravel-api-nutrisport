<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();

        $this->site = Site::create([
            'code'   => 'fr',
            'name'   => 'NutriSport France',
            'domain' => 'nutri-sport.fr',
        ]);

        $this->user = User::create([
            'name'     => 'Jean Dupont',
            'email'    => 'jean@nutrisport.fr',
            'password' => bcrypt('password'),
            'site_id'  => $this->site->id,
        ]);

        $this->product = Product::create(['name' => 'Whey Protéine', 'stock' => 10]);
        ProductPrice::create([
            'product_id' => $this->product->id,
            'site_id'    => $this->site->id,
            'price'      => 29.99,
        ]);
    }

    public function test_can_place_order_and_receive_notifications(): void
    {
        $token = JWTAuth::fromUser($this->user);

        // 1. Add item to cart
        $this->postJson("/api/fr/cart", [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ], ['Authorization' => "Bearer $token"])->assertStatus(200);

        // 2. Place order
        $response = $this->postJson("/api/fr/orders", [
            'shipping_full_name' => 'Jean Dupont',
            'shipping_address'   => '123 Rue de Paris',
            'shipping_city'      => 'Paris',
            'shipping_country'   => 'France',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201);

        // 3. Verify database
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total'   => 59.98,
        ]);

        // Check stock was decremented
        $this->assertEquals(8, $this->product->fresh()->stock);

        // 4. Verify emails are queued
        Mail::assertQueued(OrderConfirmationMail::class, 2);

        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) {
            return $mail->hasTo($this->user->email) && $mail->recipient === 'client';
        });

        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) {
            return $mail->hasTo('admin@nutrisport.fr') && $mail->recipient === 'admin';
        });
    }
}
