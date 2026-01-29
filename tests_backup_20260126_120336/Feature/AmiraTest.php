<?php

namespace Tests\Feature;

use App\Services\Amira\AmiraKnowledgeBase;
use App\Services\Amira\AmiraService;
use App\Services\Amira\ScopeValidator;
use App\Services\Amira\ToneValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmiraTest extends TestCase
{
    use RefreshDatabase;

    protected AmiraService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AmiraService(
            new AmiraKnowledgeBase(),
            new ScopeValidator(),
            new ToneValidator()
        );
    }

    /** @test */
    public function it_can_answer_in_scope_questions()
    {
        $response = $this->service->ask('Quels sont vos délais de livraison ?');
        
        $this->assertTrue($response['validated']);
        $this->assertEquals('knowledge_base', $response['source']);
        $this->assertStringContainsString('3 à 7 jours', $response['answer']);
    }

    /** @test */
    public function it_rejects_out_of_scope_questions()
    {
        $response = $this->service->ask('Comment optimiser mon business plan ?');
        
        $this->assertFalse($response['validated']);
        $this->assertEquals('fallback', $response['source']);
        $this->assertEquals(config('amira.fallback_message'), $response['answer']);
    }

    /** @test */
    public function it_rejects_forbidden_keywords()
    {
        $response = $this->service->ask('Qui est le meilleur créateur ?');
        
        $this->assertFalse($response['validated']);
        $this->assertEquals('fallback', $response['source']);
    }

    /** @test */
    public function api_endpoint_is_accessible()
    {
        $response = $this->postJson(route('api.amira.ask'), [
            'question' => 'Comment retourner un produit ?'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['answer', 'source']);
            
        $this->assertStringContainsString('14 jours', $response->json('answer'));
    }

    /** @test */
    public function api_validates_input()
    {
        $response = $this->postJson(route('api.amira.ask'), [
            'question' => ''
        ]);

        $response->assertStatus(422);
    }
}
