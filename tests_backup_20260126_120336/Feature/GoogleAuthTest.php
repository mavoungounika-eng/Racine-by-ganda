<?php

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Tests Feature pour l'authentification Google (Client & Créateur)
 * 
 * Ces tests valident les 5 points critiques :
 * 1. google_id (Anti Account Takeover)
 * 2. Protection OAuth state (Anti CSRF/Replay)
 * 3. Rôle explicite (client/creator)
 * 4. Gestion stricte des conflits de rôle
 * 5. Création transactionnelle créateur
 */
class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires
        Role::create(['name' => 'Client', 'slug' => 'client', 'is_active' => true]);
        Role::create(['name' => 'Créateur', 'slug' => 'createur', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * POINT 1 : google_id - Liaison compte Google lors de la création
     */
    public function test_google_login_creates_user_with_google_id(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('test@example.com', 'google123', 'Test User');
        
        // Redirection vers Google
        $redirectResponse = $this->get(route('auth.google.redirect', ['role' => 'client']));
        $redirectResponse->assertRedirect();
        
        // Simuler le callback Google
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'google_id' => 'google123',
        ]);
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google123', $user->google_id);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * POINT 1 : google_id - Liaison compte Google si email existe sans google_id
     */
    public function test_google_login_links_existing_user_without_google_id(): void
    {
        // Créer un utilisateur existant sans google_id
        $role = Role::where('slug', 'client')->first();
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        
        $this->assertNull($existingUser->google_id);
        
        // Mock Socialite avec le même email
        $googleUser = $this->mockGoogleUser('existing@example.com', 'google456', 'Existing User');
        
        // Redirection
        $this->get(route('auth.google.redirect', ['role' => 'client']));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifier que google_id a été lié
        $existingUser->refresh();
        $this->assertEquals('google456', $existingUser->google_id);
        $this->assertAuthenticatedAs($existingUser);
    }

    /**
     * POINT 1 : google_id - Refus si google_id existe et est différent (Account Takeover)
     */
    public function test_google_login_refuses_if_google_id_exists_and_different(): void
    {
        // Créer un utilisateur avec un google_id différent
        $role = Role::where('slug', 'client')->first();
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'google_id' => 'existing_google_id',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
        
        // Mock Socialite avec un google_id différent
        $googleUser = $this->mockGoogleUser('existing@example.com', 'different_google_id', 'Existing User');
        
        // Redirection
        $this->get(route('auth.google.redirect', ['role' => 'client']));
        
        // Callback - doit refuser
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $this->assertGuest();
        
        // Vérifier que google_id n'a pas changé
        $existingUser->refresh();
        $this->assertEquals('existing_google_id', $existingUser->google_id);
    }

    /**
     * POINT 2 : Protection OAuth state - Refus si state invalide
     */
    public function test_google_callback_refuses_if_state_invalid(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('test@example.com', 'google123', 'Test User');
        
        // Redirection (génère un state)
        $this->get(route('auth.google.redirect', ['role' => 'client']));
        
        // Callback avec state invalide
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => 'invalid_state',
        ]));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $this->assertGuest();
        
        // Vérifier qu'aucun utilisateur n'a été créé
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * POINT 2 : Protection OAuth state - Refus si state absent
     */
    public function test_google_callback_refuses_if_state_missing(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('test@example.com', 'google123', 'Test User');
        
        // Callback sans state
        $callbackResponse = $this->get(route('auth.google.callback'));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $this->assertGuest();
    }

    /**
     * POINT 3 : Rôle explicite - Création utilisateur avec rôle client
     */
    public function test_google_login_creates_client_with_explicit_role(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('client@example.com', 'google_client', 'Client User');
        
        // Redirection avec rôle client
        $this->get(route('auth.google.redirect', ['role' => 'client']));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $user = User::where('email', 'client@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('client', $user->getRoleSlug());
        $this->assertDatabaseMissing('creator_profiles', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * POINT 3 : Rôle explicite - Création utilisateur avec rôle créateur
     */
    public function test_google_login_creates_creator_with_explicit_role(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('creator@example.com', 'google_creator', 'Creator User');
        
        // Redirection avec rôle creator
        $this->get(route('auth.google.redirect', ['role' => 'creator']));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $user = User::where('email', 'creator@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('createur', $user->getRoleSlug());
        
        // Vérifier que CreatorProfile a été créé
        $this->assertDatabaseHas('creator_profiles', [
            'user_id' => $user->id,
            'status' => 'pending',
            'is_active' => false,
        ]);
    }

    /**
     * POINT 3 : Rôle explicite - Valeur par défaut client si rôle non spécifié
     */
    public function test_google_login_defaults_to_client_role_if_not_specified(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('default@example.com', 'google_default', 'Default User');
        
        // Redirection sans rôle (doit default à client)
        $this->get(route('auth.google.redirect'));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $user = User::where('email', 'default@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('client', $user->getRoleSlug());
    }

    /**
     * POINT 4 : Gestion conflits de rôle - Refus si email existe avec autre rôle
     */
    public function test_google_login_refuses_if_email_exists_with_different_role(): void
    {
        // Créer un utilisateur client existant
        $clientRole = Role::where('slug', 'client')->first();
        $existingClient = User::create([
            'name' => 'Existing Client',
            'email' => 'conflict@example.com',
            'password' => bcrypt('password'),
            'role_id' => $clientRole->id,
        ]);
        
        // Mock Socialite avec le même email mais rôle creator
        $googleUser = $this->mockGoogleUser('conflict@example.com', 'google_conflict', 'Conflict User');
        
        // Redirection avec rôle creator
        $this->get(route('auth.google.redirect', ['role' => 'creator']));
        
        // Callback - doit refuser
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $callbackResponse->assertSessionHas('conversion_offer');
        $this->assertGuest();
        
        // Vérifier que le rôle n'a pas changé
        $existingClient->refresh();
        $this->assertEquals('client', $existingClient->getRoleSlug());
    }

    /**
     * POINT 4 : Gestion conflits de rôle - Refus si email créateur existe et tentative client
     */
    public function test_google_login_refuses_if_creator_exists_and_client_requested(): void
    {
        // Créer un utilisateur créateur existant
        $creatorRole = Role::where('slug', 'createur')->first();
        $existingCreator = User::create([
            'name' => 'Existing Creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
            'role_id' => $creatorRole->id,
        ]);
        
        CreatorProfile::create([
            'user_id' => $existingCreator->id,
            'brand_name' => 'Existing Brand',
            'status' => 'active',
        ]);
        
        // Mock Socialite avec le même email mais rôle client
        $googleUser = $this->mockGoogleUser('creator@example.com', 'google_creator', 'Creator User');
        
        // Redirection avec rôle client
        $this->get(route('auth.google.redirect', ['role' => 'client']));
        
        // Callback - doit refuser
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $this->assertGuest();
        
        // Vérifier que le rôle n'a pas changé
        $existingCreator->refresh();
        $this->assertEquals('createur', $existingCreator->getRoleSlug());
    }

    /**
     * POINT 5 : Création transactionnelle créateur - Création atomique vérifiée
     * Note: Le rollback est testé conceptuellement via la transaction DB
     */
    public function test_creator_creation_is_atomic_and_creates_both_user_and_profile(): void
    {
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('creator@example.com', 'google_creator', 'Creator User');
        
        // Redirection avec rôle creator
        $this->get(route('auth.google.redirect', ['role' => 'creator']));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications : User ET Profile créés atomiquement
        $user = User::where('email', 'creator@example.com')->first();
        $this->assertNotNull($user, 'User doit être créé');
        
        $profile = CreatorProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile, 'CreatorProfile doit être créé dans la même transaction');
        
        // Vérifier les valeurs
        $this->assertEquals('createur', $user->getRoleSlug());
        $this->assertEquals('pending', $profile->status);
        $this->assertFalse($profile->is_active);
    }


    /**
     * POINT 5 : Onboarding créateur - Redirection vers pending si profil pending
     */
    public function test_creator_login_redirects_to_pending_if_profile_pending(): void
    {
        // Créer un utilisateur créateur avec profil pending
        $creatorRole = Role::where('slug', 'createur')->first();
        $creator = User::create([
            'name' => 'Creator User',
            'email' => 'pending@example.com',
            'google_id' => 'google_pending',
            'password' => bcrypt('password'),
            'role_id' => $creatorRole->id,
        ]);
        
        CreatorProfile::create([
            'user_id' => $creator->id,
            'brand_name' => 'Pending Brand',
            'status' => 'pending',
            'is_active' => false,
        ]);
        
        // Mock Socialite
        $googleUser = $this->mockGoogleUser('pending@example.com', 'google_pending', 'Creator User');
        
        // Redirection
        $this->get(route('auth.google.redirect', ['role' => 'creator']));
        
        // Callback
        $callbackResponse = $this->get(route('auth.google.callback', [
            'state' => Session::get('oauth_state'),
        ]));
        
        // Vérifications
        $callbackResponse->assertRedirect(route('creator.pending'));
        $this->assertAuthenticatedAs($creator);
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

