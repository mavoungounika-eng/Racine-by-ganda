<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoginDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }
    #[Test]
    public function it_debugs_login_flow()
    {
        // Create user with seeded role
        $role = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'email' => 'debug@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        // Enable query log
        \DB::enableQueryLog();

        // Make POST request
        $response = $this->post('/login', [
            'email' => 'debug@example.com',
            'password' => 'password123',
        ]);

        // Check if authenticated
        $isAuth = \Auth::check();
        
        // Get queries
        $queries = \DB::getQueryLog();
        
        // Dump debug info
        dump([
            'is_authenticated' => $isAuth,
            'response_status' => $response->status(),
            'redirect_location' => $response->headers->get('Location'),
            'session_data' => session()->all(),
            'query_count' => count($queries),
        ]);

        $this->assertTrue($isAuth, 'User should be authenticated');
    }
}
