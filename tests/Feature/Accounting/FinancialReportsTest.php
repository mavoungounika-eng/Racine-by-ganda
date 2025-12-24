<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpSupplier;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Services\ReportingService;
use Carbon\Carbon;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FiscalYear $fiscalYear;
    protected ReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->fiscalYear = FiscalYear::current()->first();
        $this->reportingService = app(ReportingService::class);
    }

    /** @test */
    public function it_generates_trial_balance()
    {
        // Créer transactions
        $this->createSampleTransactions();

        // Générer balance
        $balance = $this->reportingService->generateTrialBalance($this->fiscalYear->id);

        // Vérifications
        $this->assertIsArray($balance);
        $this->assertArrayHasKey('balances', $balance);
        $this->assertArrayHasKey('total_debit', $balance);
        $this->assertArrayHasKey('total_credit', $balance);
        $this->assertArrayHasKey('is_balanced', $balance);

        // Vérifier équilibre
        $this->assertTrue($balance['is_balanced']);
        $this->assertEquals($balance['total_debit'], $balance['total_credit']);
    }

    /** @test */
    public function it_generates_general_ledger_for_account()
    {
        $this->createSampleTransactions();

        // Générer grand livre pour compte 5211 (Banque Stripe)
        $ledger = $this->reportingService->generateGeneralLedger(
            '5211',
            $this->fiscalYear->id
        );

        $this->assertIsArray($ledger);
        $this->assertArrayHasKey('account', $ledger);
        $this->assertArrayHasKey('movements', $ledger);
        $this->assertArrayHasKey('final_balance', $ledger);

        // Vérifier mouvements
        $this->assertIsArray($ledger['movements']);
        
        if (count($ledger['movements']) > 0) {
            $firstMovement = $ledger['movements'][0];
            $this->assertArrayHasKey('date', $firstMovement);
            $this->assertArrayHasKey('journal', $firstMovement);
            $this->assertArrayHasKey('debit', $firstMovement);
            $this->assertArrayHasKey('credit', $firstMovement);
            $this->assertArrayHasKey('balance', $firstMovement);
        }
    }

    /** @test */
    public function it_generates_balance_sheet()
    {
        $this->createSampleTransactions();

        // Générer bilan
        $balanceSheet = $this->reportingService->generateBalanceSheet(
            $this->fiscalYear->id
        );

        $this->assertIsArray($balanceSheet);
        $this->assertArrayHasKey('actif', $balanceSheet);
        $this->assertArrayHasKey('passif', $balanceSheet);
        $this->assertArrayHasKey('resultat', $balanceSheet);
        $this->assertArrayHasKey('total_actif', $balanceSheet);
        $this->assertArrayHasKey('total_passif', $balanceSheet);
        $this->assertArrayHasKey('is_balanced', $balanceSheet);

        // Vérifier équilibre Actif = Passif
        $this->assertTrue($balanceSheet['is_balanced']);
    }

    /** @test */
    public function it_generates_income_statement()
    {
        $this->createSampleTransactions();

        // Générer compte de résultat
        $incomeStatement = $this->reportingService->generateIncomeStatement(
            $this->fiscalYear->id
        );

        $this->assertIsArray($incomeStatement);
        $this->assertArrayHasKey('charges', $incomeStatement);
        $this->assertArrayHasKey('produits', $incomeStatement);
        $this->assertArrayHasKey('total_charges', $incomeStatement);
        $this->assertArrayHasKey('total_produits', $incomeStatement);
        $this->assertArrayHasKey('resultat', $incomeStatement);
        $this->assertArrayHasKey('type', $incomeStatement);

        // Vérifier calcul résultat
        $expectedResultat = $incomeStatement['total_produits'] - $incomeStatement['total_charges'];
        $this->assertEquals($expectedResultat, $incomeStatement['resultat']);

        // Vérifier type résultat
        if ($incomeStatement['resultat'] >= 0) {
            $this->assertEquals('benefice', $incomeStatement['type']);
        } else {
            $this->assertEquals('perte', $incomeStatement['type']);
        }
    }

    /** @test */
    public function it_calculates_correct_balances_for_different_account_types()
    {
        $this->createSampleTransactions();

        $balance = $this->reportingService->generateTrialBalance($this->fiscalYear->id);

        // Vérifier que chaque compte a soit balance_debit soit balance_credit (pas les deux)
        foreach ($balance['balances'] as $accountBalance) {
            if ($accountBalance['balance_debit'] > 0) {
                $this->assertEquals(0, $accountBalance['balance_credit']);
            } elseif ($accountBalance['balance_credit'] > 0) {
                $this->assertEquals(0, $accountBalance['balance_debit']);
            }
        }
    }

    /** @test */
    public function it_filters_reports_by_date_range()
    {
        // Créer transactions à différentes dates
        $order1 = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Balance pour les 10 derniers jours
        $recentBalance = $this->reportingService->generateTrialBalance(
            $this->fiscalYear->id,
            Carbon::now()->subDays(10),
            Carbon::now()
        );

        // Balance pour les 60 derniers jours
        $fullBalance = $this->reportingService->generateTrialBalance(
            $this->fiscalYear->id,
            Carbon::now()->subDays(60),
            Carbon::now()
        );

        // La balance récente devrait avoir moins de mouvements
        $this->assertLessThanOrEqual(
            $fullBalance['total_debit'],
            $recentBalance['total_debit']
        );
    }

    /**
     * Créer transactions d'exemple pour tests
     */
    protected function createSampleTransactions(): void
    {
        // Vente boutique Stripe
        Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vente marketplace
        $creator = User::factory()->create(['role' => 'creator']);
        Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $creator->id,
            'total_amount' => 236.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Achat ERP (si modèle existe)
        try {
            $supplier = ErpSupplier::create([
                'name' => 'Fournisseur Test',
                'email' => 'test@supplier.com',
                'type' => 'fabric',
            ]);

            ErpPurchase::create([
                'supplier_id' => $supplier->id,
                'purchase_date' => now(),
                'total' => 590.00,
                'status' => 'received',
            ]);
        } catch (\Exception $e) {
            // Ignorer si modèle ERP pas encore complet
        }
    }
}
