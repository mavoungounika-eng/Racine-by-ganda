<?php

namespace Tests\Feature\Currency;

use App\Models\User;
use App\Services\Currency\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = app(CurrencyService::class);
        $this->seed(\Database\Seeders\CurrencyRateSeeder::class);
    }

    #[Test]
    public function it_can_convert_currencies()
    {
        // XAF to EUR (655.957)
        $amountXaf = 6559.57;
        $converted = $this->currencyService->convert($amountXaf, 'XAF', 'EUR');
        $this->assertEquals(10.00, $converted);

        // EUR to XAF
        $amountEur = 10;
        $convertedXaf = $this->currencyService->convert($amountEur, 'EUR', 'XAF');
        $this->assertEquals(6560, $convertedXaf); // Arrondi XAF (0 décimales)
    }

    #[Test]
    public function it_can_format_currencies()
    {
        $this->assertEquals('1 000 FCFA', $this->currencyService->format(1000, 'XAF'));
        $this->assertEquals('10,00 €', $this->currencyService->format(10, 'EUR'));
    }

    #[Test]
    public function it_detects_currency_from_phone()
    {
        $this->assertEquals('XOF', $this->currencyService->detectCurrencyFromPhone('+22501020304')); // Côte d'Ivoire
        $this->assertEquals('XAF', $this->currencyService->detectCurrencyFromPhone('+242065166110')); // Congo
        $this->assertEquals('XAF', $this->currencyService->detectCurrencyFromPhone('invalid')); // Default XAF
    }

    #[Test]
    public function middleware_sets_currency_from_query()
    {
        $response = $this->get('/?currency=EUR');
        $this->assertEquals('EUR', session('currency'));
    }

    #[Test]
    public function it_updates_user_preferred_currency_on_switch()
    {
        $user = User::factory()->create(['preferred_currency' => 'XAF']);
        
        $response = $this->actingAs($user)->post('/currency/switch', [
            'currency' => 'XOF'
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('XOF', $user->fresh()->preferred_currency);
        $this->assertEquals('XOF', session('currency'));
    }
}
