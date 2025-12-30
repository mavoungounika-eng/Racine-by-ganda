<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderVendor;
use App\Models\Payment;
use Illuminate\View\View;

class AdminFinanceController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_revenue' => Payment::where('status', 'paid')->sum('amount') ?? 0,
            'monthly_revenue' => Payment::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount') ?? 0,
            'pending_payouts' => OrderVendor::where('payout_status', 'pending')->sum('vendor_payout') ?? 0,
            'paid_commissions' => OrderVendor::where('payout_status', 'paid')->sum('commission_amount') ?? 0,
        ];

        $recentPayments = Payment::with(['order' => function($query) {
                $query->with('user');
            }])
            ->where('status', 'paid')
            ->latest()
            ->take(10)
            ->get();

        $pendingPayouts = OrderVendor::with(['vendor', 'order'])
            ->where('payout_status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.finances.index', compact('stats', 'recentPayments', 'pendingPayouts'));
    }
}
