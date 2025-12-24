<?php

namespace Tests\Feature\Auth;

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
 * Tests Feature - OAuth Facebook (Social Auth v2)
 * 
 * Phase B2 - Tests OAuth Facebook
 */
class OAuthFacebookTest extends TestCase
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
     * B2.6 - OAuth Facebook — nouveau client
     * 
     * Vérifie :
     * - User créé avec OAuth
     * - OauthAccount créé
     * - Redirection correcte
     */
    #[Test]
    public function facebook_oauth_creates_new_client_user(): void
    {
        $facebookUser = $this->mockFacebookUser('facebook@example.com', 'facebook-123', 'Facebook User');

        // Simuler la redirection OAuth
        $redirectResponse = $this->get(route('auth.social.redirect', ['provider' => 'facebook']));
        $redirectResponse->assertRedirect();

        // Simuler le callback OAuth
        $callbackResponse = $this->get(route('auth.social.callback', ['provider' => 'facebook']), [
            'state' => Session::get('oauth_state'),
        ]);

        // Vérifications
        $this->assertDatabaseHas('users', [
            'email' => 'facebook@example.com',
        ]);

        $user = User::where('email', 'facebook@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        // Vérifier que OauthAccount est créé
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider' => 'facebook',
            'provider_user_id' => 'facebook-123',
        ]);
    }

    /**
     * B2.7 - OAuth Facebook — utilisateur existant
     * 
     * Vérifie que l'utilisateur existant est reconnecté
     */
    #[Test]
    public function facebook_oauth_existing_user_is_reconnected(): void
    {
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'existing@facebook.com',
        ]);

        // Créer un OauthAccount existant
        OauthAccount::factory()->facebook()->create([
            'user_id' => $user->id,
            'provider_user_id' => 'facebook-456',
        ]);

        $this->mockFacebookUser('existing@facebook.com', 'facebook-456', 'Existing User');

        $response = $this->get(route('auth.social.callback', ['provider' => 'facebook']));

        // Vérifier qu'un seul user existe
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Helper : Mock un utilisateur Facebook Socialite
     */
    protected function mockFacebookUser(string $email, string $facebookId, string $name): SocialiteUser
    {
        $facebookUser = Mockery::mock(SocialiteUser::class);
        $facebookUser->shouldReceive('getEmail')->andReturn($email);
        $facebookUser->shouldReceive('getId')->andReturn($facebookId);
        $facebookUser->shouldReceive('getName')->andReturn($name);
        $facebookUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver')
            ->with('facebook')
            ->andReturnSelf();
            
        Socialite::shouldReceive('redirect')
            ->andReturn(redirect('https://www.facebook.com'));
            
        Socialite::shouldReceive('user')
            ->andReturn($facebookUser);

        return $facebookUser;
    }
}

