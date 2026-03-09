<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentPermission;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Sites ──────────────────────────────────────────
        $fr = Site::firstOrCreate(['code' => 'fr'], ['name' => 'NutriSport France',   'domain' => 'nutri-sport.fr']);
        $it = Site::firstOrCreate(['code' => 'it'], ['name' => 'NutriSport Italie',   'domain' => 'nutri-sport.it']);
        $be = Site::firstOrCreate(['code' => 'be'], ['name' => 'NutriSport Belgique', 'domain' => 'nutri-sport.be']);

        // ── Products ───────────────────────────────────────
        $products = [
            ['name' => 'Whey Protéine Chocolat',    'stock' => 150],
            ['name' => 'BCAA 2:1:1 Citron',         'stock' => 200],
            ['name' => 'Créatine Monohydrate',       'stock' => 80],
            ['name' => 'Barre Énergétique Vanille',  'stock' => 300],
            ['name' => 'Oméga-3 Premium',            'stock' => 120],
        ];

        foreach ($products as $data) {
            $product = Product::create($data);

            // Different price per site
            ProductPrice::create(['product_id' => $product->id, 'site_id' => $fr->id, 'price' => fake()->randomFloat(2, 15, 60)]);
            ProductPrice::create(['product_id' => $product->id, 'site_id' => $it->id, 'price' => fake()->randomFloat(2, 14, 58)]);
            ProductPrice::create(['product_id' => $product->id, 'site_id' => $be->id, 'price' => fake()->randomFloat(2, 16, 62)]);
        }

        // ── Agent ID=1 (super admin — bypasses all permissions) ──
        Agent::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@nutrisport.fr',
            'password' => Hash::make('password'),
        ]);

        // ── Agent with limited permissions ─────────────────
        $agent2 = Agent::create([
            'name'     => 'Agent Commandes',
            'email'    => 'commandes@nutrisport.fr',
            'password' => Hash::make('password'),
        ]);
        AgentPermission::create(['agent_id' => $agent2->id, 'permission' => 'view_orders']);

        $agent3 = Agent::create([
            'name'     => 'Agent Catalogue',
            'email'    => 'catalogue@nutrisport.fr',
            'password' => Hash::make('password'),
        ]);
        AgentPermission::create(['agent_id' => $agent3->id, 'permission' => 'create_products']);
    }
}
