<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Agent;
use Illuminate\Support\Facades\Hash;

class BackofficeAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_agent_can_login_with_correct_credentials(): void
    {
        $agent = Agent::create([
            'name' => 'Administrator',
            'email' => 'admin@nutrisport.com',
            'password' => Hash::make('Admin12345!'),
        ]);

        $response = $this->postJson('/api/backoffice/login', [
            'email' => 'admin@nutrisport.com',
            'password' => 'Admin12345!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in',
                 ]);
    }

    public function test_agent_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/backoffice/login', [
            'email' => 'admin@nutrisport.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Identifiants incorrects.']);
    }
}
