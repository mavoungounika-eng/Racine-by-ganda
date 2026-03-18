<?php

namespace App\Http\Controllers;

use App\Services\Currency\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Changer la devise en session
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => ['required', 'string', Rule::in(config('currency.supported'))]
        ]);

        $this->currencyService->setSessionCurrency($request->currency);

        if (auth()->check()) {
            auth()->user()->update([
                'preferred_currency' => $request->currency
            ]);
        }

        return response()->json([
            'success' => true,
            'currency' => $request->currency,
            'symbol' => config("currency.symbols.{$request->currency}"),
        ]);
    }

    /**
     * API publique pour les taux de change
     */
    public function rates(): JsonResponse
    {
        $data = Cache::remember('api_currency_rates', 86400, function () {
            return [
                'rates' => config('currency.rates'),
                'symbols' => config('currency.symbols'),
            ];
        });

        return response()->json($data);
    }

    /**
     * API publique pour la conversion
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => ['required', Rule::in(config('currency.supported'))],
            'to' => ['required', Rule::in(config('currency.supported'))],
        ]);

        $converted = $this->currencyService->convert(
            $request->amount,
            $request->from,
            $request->to
        );

        return response()->json([
            'original' => $request->amount,
            'converted' => $converted,
            'from' => $request->from,
            'to' => $request->to,
            'rate' => $this->currencyService->getRate($request->from, $request->to),
            'formatted' => $this->currencyService->format($converted, $request->to),
        ]);
    }
}
