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

        // Simuler le state dans la session pour passer la validation CSRF dans le contrôleur
        $state = \Illuminate\Support\Str::random(40);
        // Simuler le callback OAuth avec le state correct
        $callbackResponse = $this->withSession([
            'oauth_state' => $state,
            'oauth_provider' => 'google',
            'social_login_context' => 'boutique',
            'oauth_role' => 'client',
        ]);
        $callbackResponse = $this->get(route('auth.social.callback', [
            'provider' => 'google',
            'state' => $state,
            'role' => 'client',
        ]));

        // Vérifications DB
        $this->assertDatabaseHas('users', [
            'email' => 'client@gmail.com',
        ]);

        $user = User::where('email', 'client@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        // Vérifier que le UserContext est en session
        $this->assertTrue(Session::has('user_context'));
        $contextArray = Session::get('user_context');
        $context = \App\DTOs\Auth\UserContext::fromArray($contextArray);
        $this->assertEquals('client', $context->role);

        // Redirection vers le dashboard client (via PostLoginDecisionEngine)
        $callbackResponse->assertRedirect(route('account.dashboard'));

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

        // Simuler le state
        $state = \Illuminate\Support\Str::random(40);
        // Simuler le state
        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_provider' => 'google',
            'social_login_context' => 'boutique',
            'oauth_role' => 'creator',
        ])->get(route('auth.social.callback', [
            'provider' => 'google',
            'state' => $state,
            'role' => 'creator',
        ]));
        
        if (session('error')) { dump(session('error')); }
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('creator.pending'));
        $this->assertAuthenticatedAs($user);
        
        // Vérifier context
        $this->assertTrue(Session::has('user_context'));
        $userContext = Session::get('user_context');
        $this->assertEquals('pending', is_array($userContext) ? $userContext['creator_status'] : $userContext->creatorStatus);
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

        // Simuler le state
        $state = \Illuminate\Support\Str::random(40);
        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_provider' => 'google',
            'social_login_context' => 'boutique',
            'oauth_role' => 'client',
        ])->get(route('auth.social.callback', [
            'provider' => 'google',
            'state' => $state,
        ]));

        // Vérifier qu'un seul user existe
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
        
        // Vérifier context
        $this->assertTrue(Session::has('user_context'));
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
        $googleUser->shouldReceive('getRaw')->andReturn(['sub' => $googleId]);
        $googleUser->shouldReceive('token')->andReturn('access-token');

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

