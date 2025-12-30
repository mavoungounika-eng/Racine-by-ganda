<?php

namespace Tests\Feature\Auth;

use App\Models\CreatorProfile;
use App\Models\OauthAccount;
use App\Models\Role;
use App\Models\User;
use Database\Factories\OauthAccountFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests Feature - OAuth Google (Social Auth v2)
 * 
 * Phase B2 - Tests OAuth Google
 * ⚠️ RÈGLE D'OR : On mock Socialite, on ne touche PAS aux vrais providers
 */
class OAuthGoogleClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * B2.1 - OAuth Google — nouveau client
     * 
     * Vérifie :
     * - User créé avec OAuth
     * - OauthAccount créé
     * - Redirection correcte
     */
    #[Test]
    public function google_oauth_creates_new_client_user(): void
    {
        $googleUser = $this->mockGoogleUser('client@gmail.com', 'google-123', 'Client Test');

        // Simuler la redirection OAuth
        $redirectResponse = $this->get(route('auth.social.redirect', ['provider' => 'google']));
        $redirectResponse->assertRedirect();

        // Simuler le callback OAuth
        $callbackResponse = $this->get(route('auth.social.callback', ['provider' => 'google']), [
            'state' => Session::get('oauth_state'),
        ]);

        // Vérifications
        $this->assertDatabaseHas('users', [
            'email' => 'client@gmail.com',
        ]);

        $user = User::where('email', 'client@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        // Vérifier que OauthAccount est créé
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    /**
     * B2.2 - OAuth Google — créateur pending
     * 
     * Vérifie que le créateur pending est redirigé vers pending
     */
    #[Test]
    public function google_oauth_creator_is_redirected_to_pending(): void
    {
        $role = Role::where('slug', 'createur')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'creator@gmail.com',
        ]);

        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Créer un OauthAccount existant
        OauthAccount::factory()->google()->create([
            'user_id' => $user->id,
            'provider_user_id' => 'google-123',
        ]);

        $this->mockGoogleUser('creator@gmail.com', 'google-123', 'Creator User');

        $this->actingAs($user);

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));
        $response->assertRedirect(route('creator.pending'));
    }

    /**
     * B2.3 - OAuth Google — utilisateur existant se reconnecte
     * 
     * Vérifie que l'utilisateur existant est reconnecté (pas de doublon)
     */
    #[Test]
    public function google_oauth_existing_user_is_reconnected(): void
    {
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'existing@gmail.com',
        ]);

        // Créer un OauthAccount existant
        OauthAccount::factory()->google()->create([
            'user_id' => $user->id,
            'provider_user_id' => 'google-456',
        ]);

        $this->mockGoogleUser('existing@gmail.com', 'google-456', 'Existing User');

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

        // Vérifier qu'un seul user existe
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Helper : Mock un utilisateur Google Socialite
     */
    protected function mockGoogleUser(string $email, string $googleId, string $name): SocialiteUser
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getId')->andReturn($googleId);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();
            
        Socialite::shouldReceive('redirect')
            ->andReturn(redirect('https://accounts.google.com'));
            
        Socialite::shouldReceive('user')
            ->andReturn($googleUser);

        return $googleUser;
    }
}

