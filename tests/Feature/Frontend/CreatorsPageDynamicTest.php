<?php

namespace Tests\Feature\Frontend;

use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Régression T-02 — Page /createurs dynamique au lieu de hardcodée
 *
 * Avant : Section featured affichait du contenu hardcodé (Amina Diallo, Dakar, 85 créations)
 * Après : Section featured utilise un CreatorProfile réel de la DB (is_featured=true ou le premier actif)
 *
 * Ce test verrouille :
 * 1. La route /createurs passe $featuredCreator au template
 * 2. Le featured creator est celui marqué is_featured=true ou le premier actif
 * 3. La vue n'affiche plus le hardcode "Amina Diallo"
 */
class CreatorsPageDynamicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creators_page_passes_featured_creator(): void
    {
        // Créer des créateurs
        $user1 = User::factory()->create(['name' => 'Créateur 1']);
        $featured = CreatorProfile::factory()
            ->for($user1)
            ->create([
                'brand_name' => 'Créateur Vedette',
                'is_featured' => true,
                'is_active' => true,
            ]);

        $user2 = User::factory()->create(['name' => 'Créateur 2']);
        CreatorProfile::factory()
            ->for($user2)
            ->create(['is_featured' => false, 'is_active' => true]);

        // Requête à la page créateurs
        $response = $this->get(route('frontend.creators'));

        $response->assertSuccessful();
        $response->assertViewHas('featuredCreator');
        $response->assertViewHas('creators');

        // Vérifier que le featured creator passé est bien celui marqué is_featured=true
        $this->assertEquals(
            $featured->id,
            $response->original['featuredCreator']->id,
            'La page doit passer le CreatorProfile marqué is_featured=true comme featured'
        );
    }

    #[Test]
    public function featured_creator_falls_back_to_first_active_if_none_marked(): void
    {
        // Créer des créateurs, AUCUN marqué is_featured
        $user1 = User::factory()->create(['name' => 'Premier Créateur']);
        $first = CreatorProfile::factory()
            ->for($user1)
            ->create(['is_featured' => false, 'is_active' => true, 'created_at' => now()->subDays(10)]);

        $user2 = User::factory()->create(['name' => 'Deuxième Créateur']);
        CreatorProfile::factory()
            ->for($user2)
            ->create(['is_featured' => false, 'is_active' => true, 'created_at' => now()]);

        $response = $this->get(route('frontend.creators'));

        // Vérifier que le featured est le premier créé (par created_at)
        $this->assertEquals(
            $first->id,
            $response->original['featuredCreator']->id,
            'Si aucun is_featured, doit fallback au créateur le plus ancien actif'
        );
    }

    #[Test]
    public function featured_section_renders_with_featured_creator_data(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $creator = CreatorProfile::factory()
            ->for($user)
            ->create([
                'brand_name' => 'Boutique Teste',
                'location' => 'Accra',
                'bio' => 'Mon histoire inspirante',
                'is_featured' => true,
                'is_active' => true,
            ]);

        $response = $this->get(route('frontend.creators'));

        // La vue doit afficher le nom du featured creator, NICHT "Amina Diallo"
        $response->assertSee('Boutique Teste');
        $response->assertSee('Mon histoire inspirante');
        $response->assertSee('Accra');

        // Vérifier que le hardcode "Amina Diallo" n'est plus présent
        $response->assertDontSee('Amina Diallo');
    }

    #[Test]
    public function creator_profile_has_is_featured_in_fillable(): void
    {
        $creator = new CreatorProfile();
        $this->assertContains(
            'is_featured',
            $creator->getFillable(),
            'CreatorProfile doit avoir is_featured dans $fillable'
        );
    }

    #[Test]
    public function creators_grid_still_shows_all_creators(): void
    {
        // Créer 15 créateurs (plus que la pagination de 12)
        User::factory(15)->create()->each(function ($user) {
            CreatorProfile::factory()
                ->for($user)
                ->create(['is_active' => true]);
        });

        $response = $this->get(route('frontend.creators'));

        // Vérifier que la grille des créateurs est toujours paginée
        $response->assertViewHas('creators');
        $this->assertEquals(12, $response->original['creators']->count());
    }
}
