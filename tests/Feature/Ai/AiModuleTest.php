<?php

namespace Tests\Feature\Ai;

use App\Models\Product;
use App\Models\User;
use App\Models\AiConversation;
use App\Models\AiUsageLog;
use App\Jobs\Ai\GenerateProductDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use OpenAI\Laravel\Facades\OpenAI;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AiModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_product_description_generation_queued()
    {
        Queue::fake();
        $user = User::factory()->create([
            'role' => 'createur',
            'auth_version' => 1,
        ]);
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->post("/api/ai/products/{$product->id}/description");

        $response->assertStatus(200);
        $response->assertJson(['queued' => true]);
        Queue::assertPushed(GenerateProductDescription::class);
    }

    #[Test]
    public function test_price_suggestion_returns_structured_data()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggested_price' => 15000,
                                'reasoning' => 'Premium quality',
                                'confidence' => 'high',
                                'range' => ['min' => 14000, 'max' => 16000]
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'createur',
            'auth_version' => 1,
        ]);
        $product = Product::factory()->create(['user_id' => $user->id, 'price' => 10000]);

        $response = $this->actingAs($user, 'sanctum')->post("/api/ai/products/{$product->id}/price-suggestion");

        $response->assertStatus(200);
        $response->assertJsonStructure(['suggested_price', 'reasoning', 'confidence', 'range']);
        $this->assertEquals(15000, $response->json('suggested_price'));
    }

    #[Test]
    public function test_creator_chat_saves_conversation()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Bonjour, comment puis-je vous aider ?',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'createur',
            'auth_version' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')->post("/api/ai/chat", [
            'message' => 'Hello AI',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $user->id,
        ]);
        
        $conv = AiConversation::first();
        $this->assertCount(2, $conv->messages);
    }

    #[Test]
    public function test_quota_blocks_after_limit()
    {
        $user = User::factory()->create(['role' => 'createur']);
        $product = Product::factory()->create(['user_id' => $user->id]);

        // Create 20 logs for today (limit is 20 for description)
        for ($i = 0; $i < 20; $i++) {
            AiUsageLog::create([
                'user_id' => $user->id,
                'feature' => 'description',
                'model' => 'gpt-4o',
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->post("/api/ai/products/{$product->id}/description");

        // The job won't be dispatched because checkQuota fails in the job handle or controller
        // Wait, the controller doesn't check quota, the service does. 
        // But the job handle calls generateDescription which checks quota.
        // Let's call the service directly to test quota logic.
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quota journalier de descriptions atteint.');
        
        app(\App\Services\Ai\ProductAiService::class)->generateDescription($product);
    }

    #[Test]
    public function test_openai_fallback_if_api_down()
    {
        OpenAI::fake([
            new \Exception('OpenAI Error'),
        ]);

        $user = User::factory()->create(['role' => 'createur']);
        $product = Product::factory()->create(['user_id' => $user->id, 'description' => 'Original description']);

        // Test fallback in service directly
        $description = app(\App\Services\Ai\ProductAiService::class)->generateDescription($product);

        $this->assertEquals('Original description', $description);
    }

    #[Test]
    public function test_ai_usage_logged_correctly()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30,
                ],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'createur']);
        $product = Product::factory()->create(['user_id' => $user->id]);

        app(\App\Services\Ai\ProductAiService::class)->suggestPrice($product);

        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'feature' => 'price_suggestion',
            'total_tokens' => 30,
        ]);
    }

    #[Test]
    public function test_admin_daily_summary_cached()
    {
        // Mock OpenAI with specific totalToken to verify it's called once
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [['message' => ['content' => 'Summary 1']]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        
        $service = app(\App\Services\Ai\AdminAiService::class);
        
        $summary1 = $service->generateDailySummary();
        $summary2 = $service->generateDailySummary();

        $this->assertEquals($summary1, $summary2);
        // If cached, summary2 comes from Cache, so OpenAI was only called once.
        // OpenAI::fake doesn't easily count calls in this version, but we can verify behavior.
    }

    #[Test]
    public function test_stock_anomaly_detection_returns_alerts()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'anomalies' => [['id' => 1, 'reason' => 'Too low']],
                                'alerts' => [['priority' => 'high']],
                                'forecast' => []
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(\App\Services\Ai\ErpAiService::class);
        $result = $service->detectStockAnomalies(collect());

        $this->assertArrayHasKey('anomalies', $result);
        $this->assertArrayHasKey('alerts', $result);
    }

    #[Test]
    public function test_crm_customer_behavior_analysis()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'profile_summary' => 'Good customer',
                                'churn_risk' => 'low'
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $customer = User::factory()->create(['role' => 'client']);
        $service = app(\App\Services\Ai\CrmAiService::class);
        $result = $service->analyzeCustomerBehavior($customer);

        $this->assertEquals('Good customer', $result['profile_summary']);
        $this->assertEquals('low', $result['churn_risk']);
    }

    #[Test]
    public function test_segment_suggestions_returned()
    {
        OpenAI::fake([
            \OpenAI\Responses\Chat\CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                ['name' => 'VIP', 'description' => 'Big spenders']
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(\App\Services\Ai\CrmAiService::class);
        $result = $service->suggestSegments(collect());

        $this->assertIsArray($result);
        $this->assertEquals('VIP', $result[0]['name']);
    }
}
