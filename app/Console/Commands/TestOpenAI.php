<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class TestOpenAI extends Command
{
    protected $signature = 'openai:test';
    protected $description = 'Test OpenAI connection';

    public function handle()
    {
        $this->info('🧪 Testing OpenAI connection...');
        
        // Vérifie la clé API
        $apiKey = env('OPENAI_API_KEY');
        
        if (empty($apiKey) || strpos($apiKey, 'sk-ta_clé_api') !== false) {
            $this->error('❌ ERREUR: Clé API OpenAI non configurée !');
            $this->line('');
            $this->line('🛠️  Solutions:');
            $this->line('1. Ouvre ton fichier .env');
            $this->line('2. Remplace "sk-ta_clé_api_openai_ici" par ta vraie clé');
            $this->line('3. Sauvegarde le fichier');
            $this->line('4. Exécute: php artisan config:clear');
            $this->line('');
            $this->line('📍 Obtiens ta clé sur: https://platform.openai.com/api-keys');
            return 1;
        }
        
        $this->info('✅ Clé API trouvée: ' . substr($apiKey, 0, 12) . '...');
        
        try {
            // Test simple
            $this->line('📤 Envoi du test à OpenAI...');
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Réponds "SUCCESS" en majuscules.']
                ],
                'max_tokens' => 10,
            ]);
            
            $message = trim($response->choices[0]->message->content);
            
            $this->info('🎉 CONNEXION RÉUSSIE !');
            $this->info('📨 Réponse: ' . $message);
            
            // Affiche les détails
            $this->line('');
            $this->info('📊 Détails de la réponse:');
            $this->line('   Modèle utilisé: ' . $response->model);
            $this->line('   Tokens utilisés: ' . $response->usage->totalTokens);
            $this->line('   Prompt tokens: ' . $response->usage->promptTokens);
            $this->line('   Completion tokens: ' . $response->usage->completionTokens);
            
            // Test avec différents modèles (optionnel)
            $this->line('');
            $this->info('🔍 Test des modèles disponibles:');
            
            $modelsToTest = ['gpt-3.5-turbo'];
            
            foreach ($modelsToTest as $model) {
                $this->line("   Testing {$model}...");
                try {
                    $testResponse = OpenAI::chat()->create([
                        'model' => $model,
                        'messages' => [
                            ['role' => 'user', 'content' => 'Bonjour']
                        ],
                        'max_tokens' => 5,
                    ]);
                    $this->info("     ✅ {$model}: Fonctionne");
                } catch (\Exception $e) {
                    $this->error("     ❌ {$model}: " . $e->getMessage());
                }
            }
            
            $this->line('');
            $this->info('🚀 OpenAI est prêt à être utilisé dans ton projet Laravel !');
            $this->line('');
            $this->line('Prochaines étapes:');
            $this->line('1. Crée un contrôleur: php artisan make:controller Api/AiController');
            $this->line('2. Ajoute des routes dans routes/api.php');
            $this->line('3. Utilise le service AI dans ton application');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ ERREUR de connexion OpenAI !');
            $this->error('Message: ' . $e->getMessage());
            $this->line('');
            
            if (strpos($e->getMessage(), 'cURL error 6') !== false) {
                $this->line('📡 Problème de connexion internet');
            } elseif (strpos($e->getMessage(), 'Incorrect API key') !== false) {
                $this->line('🔑 Clé API invalide - vérifie ta clé OpenAI');
            } elseif (strpos($e->getMessage(), 'rate limit') !== false) {
                $this->line('⏰ Limite de taux dépassée - attends quelques minutes');
            } elseif (strpos($e->getMessage(), 'insufficient_quota') !== false) {
                $this->line('💳 Crédits insuffisants - recharge ton compte OpenAI');
            }
            
            return 1;
        }
    }
}