<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentAuditLog;
use App\Models\PaymentProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentProviderController extends Controller
{
    /**
     * Liste des providers
     *
     * @return View
     */
    public function index(): View
    {
        $this->authorize('payments.view');

        $providers = PaymentProvider::orderBy('priority')->get();

        return view('admin.payments.providers.index', compact('providers'));
    }

    /**
     * Mettre à jour un provider
     *
     * @param Request $request
     * @param PaymentProvider $provider
     * @return RedirectResponse
     */
    public function update(Request $request, PaymentProvider $provider): RedirectResponse
    {
        $this->authorize('payments.config');

        $validated = $request->validate([
            'is_enabled' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'currency' => 'sometimes|string|size:3',
        ]);

        $oldValues = [
            'is_enabled' => $provider->is_enabled,
            'priority' => $provider->priority,
            'currency' => $provider->currency,
        ];

        $provider->update($validated);

        // Audit log
        PaymentAuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'provider.update',
            'target_type' => PaymentProvider::class,
            'target_id' => $provider->id,
            'diff' => [
                'old' => $oldValues,
                'new' => $validated,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.payments.providers.index')
            ->with('success', 'Provider mis à jour avec succès.');
    }
}




