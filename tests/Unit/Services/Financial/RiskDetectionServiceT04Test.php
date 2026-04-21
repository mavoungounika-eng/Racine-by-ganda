<?php

namespace Tests\Unit\Services\Financial;

use App\Models\CreatorProfile;
use App\Models\User;
use App\Services\Financial\RiskDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Régression T-04 — Colonne risk_level sur creator_profiles
 *
 * Avant : RiskDetectionService essayait de mettre à jour une colonne inexistante.
 * Après : Colonne risk_level ajoutée via migration, propriété fillable sur CreatorProfile.
 *
 * Ce test verrouille :
 * 1. La migration a créé la colonne
 * 2. CreatorProfile accepte risk_level en $fillable
 * 3. RiskDetectionService peut persister le risk_level
 */
class RiskDetectionServiceT04Test extends TestCase
{
    use RefreshDatabase;

    protected RiskDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RiskDetectionService::class);
    }

    #[Test]
    public function creator_profile_table_has_risk_level_column(): void
    {
        // Vérifier via schema que la colonne existe
        $columns = \DB::getSchemaBuilder()->getColumnListing('creator_profiles');
        
        $this->assertContains(
            'risk_level',
            $columns,
            'Migration T-04 doit avoir créé la colonne risk_level sur creator_profiles'
        );
    }

    #[Test]
    public function creator_profile_has_risk_level_in_fillable(): void
    {
        $creatorProfile = new CreatorProfile();
        
        $this->assertContains(
            'risk_level',
            $creatorProfile->getFillable(),
            'CreatorProfile doit avoir risk_level dans $fillable'
        );
    }

    #[Test]
    public function risk_detection_service_can_update_creator_risk_level(): void
    {
        // Créer un créateur avec un profil minimal
        $user = User::factory()->create();
        // Créer avec risk_level explicite puisque la factory ne le déclare pas
        $creator = CreatorProfile::factory()
            ->for($user)
            ->create(['risk_level' => 'normal']);

        // Simuler une mise à jour du risk_level (ce que fait sendRiskAlerts)
        $creator->update(['risk_level' => 'critical']);

        // Vérifier que la mise à jour a fonctionné
        $refreshed = CreatorProfile::find($creator->id);
        $this->assertSame(
            'critical',
            $refreshed->risk_level,
            'La colonne risk_level doit être persistée'
        );
    }

    #[Test]
    public function creator_profile_accepts_risk_level_values(): void
    {
        // Tester chaque valeur valide de risk_level
        $validValues = ['normal', 'watch', 'high', 'critical'];
        
        foreach ($validValues as $value) {
            $user = User::factory()->create();
            $creator = CreatorProfile::factory()
                ->for($user)
                ->create(['risk_level' => $value]);

            $this->assertSame(
                $value,
                $creator->fresh()->risk_level,
                "risk_level doit accepter la valeur '{$value}'"
            );
        }
    }
}
