<?php

namespace Tests\Feature\Auth;

use App\Models\OauthAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests Feature - OAuth Apple (Social Auth v2)
 * 
 * Phase B2 - Tests OAuth Apple
 * Spécificité : Gestion des emails masqués
 */
class OAuthAppleTest extends TestCase
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
     * B2.4 - OAuth Apple — email masqué
     * 
     * Vérifie :
     * - Pas de crash
     * - Email temporaire accepté (ou null)
     * - User créé avec provider_user_id
     */
    #[Test]
    public function apple_oauth_with_hidden_email_creates_temp_email(): void
    {
        $appleUser = $this->mockAppleUser(null, 'apple-999', 'Apple User');

        // Simuler la redirection OAuth
        $redirectResponse = $this->get(route('auth.social.redirect', ['provider' => 'apple']));
        $redirectResponse->assertRedirect();

        // Simuler le callback OAuth
        $callbackResponse = $this->get(route('auth.social.callback', ['provider' => 'apple']), [
            'state' => Session::get('oauth_state'),
        ]);

        // Vérifications
        $this->assertDatabaseCount('users', 1);
        
        $oauthAccount = OauthAccount::where('provider', 'apple')
            ->where('provider_user_id', 'apple-999')
            ->first();
            
        $this->assertNotNull($oauthAccount);
        $this->assertNull($oauthAccount->provider_email); // Email masqué
        $this->assertAuthenticatedAs($oauthAccount->user);
    }

    /**
     * B2.5 - OAuth Apple — email disponible
     * 
     * Vérifie que si l'email est disponible, il est utilisé
     */
    #[Test]
    public function apple_oauth_with_email_uses_provided_email(): void
    {
        $appleUser = $this->mockAppleUser('apple@example.com', 'apple-888', 'Apple User With Email');

        $redirectResponse = $this->get(route('auth.social.redirect', ['provider' => 'apple']));
        $redirectResponse->assertRedirect();

        $callbackResponse = $this->get(route('auth.social.callback', ['provider' => 'apple']), [
            'state' => Session::get('oauth_state'),
        ]);

        // Vérifications
        $this->assertDatabaseHas('users', [
            'email' => 'apple@example.com',
        ]);

        $user = User::where('email', 'apple@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Helper : Mock un utilisateur Apple Socialite
     */
    protected function mockAppleUser(?string $email, string $appleId, string $name): SocialiteUser
    {
        $appleUser = Mockery::mock(SocialiteUser::class);
        $appleUser->shouldReceive('getEmail')->andReturn($email);
        $appleUser->shouldReceive('getId')->andReturn($appleId);
        $appleUser->shouldReceive('getName')->andReturn($name);
        $appleUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver')
            ->with('apple')
            ->andReturnSelf();
            
        Socialite::shouldReceive('redirect')
            ->andReturn(redirect('https://appleid.apple.com'));
            
        Socialite::shouldReceive('user')
            ->andReturn($appleUser);

        return $appleUser;
    }
}



