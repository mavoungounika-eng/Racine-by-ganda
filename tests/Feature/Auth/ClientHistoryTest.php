<?php

namespace Tests\Feature\Auth;

use App\Models\Address;
use App\Models\CreatorProfile;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests Feature - Préservation Historique Client
 * 
 * Phase B3 - Tests Historique Client (CRITIQUE)
 * 
 * 🎯 TEST CLÉ : Prouve NOIR SUR BLANC qu'aucune donnée n'est perdue
 */
class ClientHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
    }

    /**
     * B3.1 - Client → créateur : historique intact
     * 
     * Vérifie que toutes les données client sont préservées lors du passage créateur
     */
    #[Test]
    public function client_history_is_preserved_after_becoming_creator(): void
    {
        $clientRole = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        // Créer des données client
        $orders = Order::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $addresses = Address::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        // Sauvegarder les IDs pour vérification
        $orderIds = $orders->pluck('id')->toArray();
        $addressIds = $addresses->pluck('id')->toArray();
        $userId = $user->id;

        // Devient créateur
        $creatorRole = Role::where('slug', 'createur')->first();
        $user->update(['role_id' => $creatorRole->id]);
        
        CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Vérifications : toutes les données doivent être préservées
        $this->assertCount(3, Order::where('user_id', $userId)->get());
        $this->assertCount(2, Address::where('user_id', $userId)->get());

        // Vérifier que les IDs sont identiques
        $preservedOrderIds = Order::where('user_id', $userId)->pluck('id')->toArray();
        $preservedAddressIds = Address::where('user_id', $userId)->pluck('id')->toArray();

        $this->assertEquals($orderIds, $preservedOrderIds);
        $this->assertEquals($addressIds, $preservedAddressIds);

        // Vérifier que users.id n'a pas changé
        $this->assertEquals($userId, $user->id);
    }

    /**
     * B3.2 - Validation admin ne modifie pas l'historique
     * 
     * Vérifie que la validation admin (creator_profile.status = 'active') 
     * ne modifie pas l'historique client
     */
    #[Test]
    public function admin_validation_does_not_modify_client_history(): void
    {
        $clientRole = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        // Créer des données client
        $orders = Order::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $userId = $user->id;
        $orderIds = $orders->pluck('id')->toArray();

        // Devient créateur
        $creatorRole = Role::where('slug', 'createur')->first();
        $user->update(['role_id' => $creatorRole->id]);
        
        $creatorProfile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Validation admin
        $creatorProfile->update(['status' => 'active']);

        // Vérifications : l'historique doit être intact
        $this->assertCount(2, Order::where('user_id', $userId)->get());
        
        $preservedOrderIds = Order::where('user_id', $userId)->pluck('id')->toArray();
        $this->assertEquals($orderIds, $preservedOrderIds);

        // Vérifier que users.id n'a pas changé
        $this->assertEquals($userId, $user->id);
    }

    /**
     * B3.3 - Suspension créateur ne modifie pas l'historique
     * 
     * Vérifie que la suspension d'un créateur ne modifie pas l'historique client
     */
    #[Test]
    public function creator_suspension_does_not_modify_client_history(): void
    {
        $clientRole = Role::where('slug', 'client')->first();
        
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        // Créer des données client
        $orders = Order::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $userId = $user->id;
        $orderIds = $orders->pluck('id')->toArray();

        // Devient créateur actif
        $creatorRole = Role::where('slug', 'createur')->first();
        $user->update(['role_id' => $creatorRole->id]);
        
        $creatorProfile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Suspension
        $creatorProfile->update(['status' => 'suspended']);

        // Vérifications : l'historique doit être intact
        $this->assertCount(2, Order::where('user_id', $userId)->get());
        
        $preservedOrderIds = Order::where('user_id', $userId)->pluck('id')->toArray();
        $this->assertEquals($orderIds, $preservedOrderIds);

        // Vérifier que users.id n'a pas changé
        $this->assertEquals($userId, $user->id);
    }
}








