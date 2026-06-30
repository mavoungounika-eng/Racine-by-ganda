<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\ERP\Models\ErpProductDetail;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;
use Tests\TestCase;

class PosProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveDeviceWithUser(User $user): PosDevice
    {
        return PosDevice::create([
            'machine_id' => (string) Str::uuid(),
            'name' => 'POS-Device',
            'machine_secret' => 'secret-key',
            'status' => 'active',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
    }

    private function authHeaderForDevice(PosDevice $device): array
    {
        $token = app(DeviceAuthService::class)->generateToken($device->machine_id);

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_authenticated_device_can_list_products(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/pos/products', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['data', 'pagination']]);
    }

    public function test_product_list_is_paginated(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/pos/products?per_page=20', $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.pagination.per_page', 20);
    }

    public function test_can_filter_products_by_category(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        Product::factory()->count(2)->create(['category_id' => $catA->id]);
        Product::factory()->count(1)->create(['category_id' => $catB->id]);

        $response = $this->getJson("/api/pos/products?category_id={$catA->id}", $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_can_filter_in_stock_products_only(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        Product::factory()->create(['stock' => 10]);
        Product::factory()->create(['stock' => 0]);

        $response = $this->getJson('/api/pos/products?in_stock=true', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['in_stock']);
    }

    public function test_can_search_products_by_name(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        Product::factory()->create(['title' => 'Chaussure Cuir']);
        Product::factory()->create(['title' => 'Sac de voyage']);

        $response = $this->getJson('/api/pos/products/search?q=chaussure', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.results'));
    }

    public function test_can_search_products_by_sku(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $product = Product::factory()->create(['title' => 'Produit SKU']);
        ErpProductDetail::create([
            'product_id' => $product->id,
            'sku' => 'SKU-TEST-001',
            'barcode' => 'BAR-TEST-001',
            'cost_price' => 100.00,
        ]);

        $response = $this->getJson('/api/pos/products/search?q=SKU-TEST-001', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.results'));
    }

    public function test_search_returns_max_10_results(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        Product::factory()->count(15)->create(['title' => 'Produit Test']);

        $response = $this->getJson('/api/pos/products/search?q=Produit', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data.results')));
    }

    public function test_can_get_single_product_detail(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $product = Product::factory()->create();

        $response = $this->getJson("/api/pos/products/{$product->id}", $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $product->id);
    }

    public function test_can_list_categories(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        Category::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/pos/products/categories', $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.categories'));
    }

    public function test_inactive_products_excluded_from_catalog(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        Product::factory()->inactive()->create();
        Product::factory()->create();

        $response = $this->getJson('/api/pos/products', $this->authHeaderForDevice($device));
        $items = $response->json('data.data');

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['is_active']);
    }

    public function test_out_of_stock_products_flagged_correctly(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $product = Product::factory()->create(['stock' => 0]);

        $response = $this->getJson("/api/pos/products/{$product->id}", $this->authHeaderForDevice($device));

        $response->assertStatus(200);
        $response->assertJsonPath('data.in_stock', false);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/pos/products');
        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }
}
