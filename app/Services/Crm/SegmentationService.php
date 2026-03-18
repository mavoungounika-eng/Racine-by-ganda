<?php

namespace App\Services\Crm;

use App\Models\User;
use App\Models\CustomerSegment;
use App\Models\CustomerTag;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\LoyaltyPoint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SegmentationService
{
    /**
     * Calcule toutes les métriques d'un client.
     */
    public function evaluateCustomer(User $customer): array
    {
        $cacheKey = "crm_metrics:{$customer->id}";
        
        return Cache::remember($cacheKey, config('crm.metrics_cache_ttl', 3600), function () use ($customer) {
            $webOrders = Order::where('user_id', $customer->id)->where('status', 'completed')->get();
            $posSales  = PosSale::where('customer_id', $customer->id)->where('status', 'finalized')->get();

            $totalSpent = ($webOrders->sum('total_amount') + $posSales->sum('total_amount'));
            $webCount   = $webOrders->count();
            $posCount   = $posSales->count();
            
            $channel = 'none';
            if ($webCount > 0 && $posCount > 0) $channel = 'both';
            elseif ($webCount > 0) $channel = 'web';
            elseif ($posCount > 0) $channel = 'pos';

            $lastWebOrder = Order::where('user_id', $customer->id)->latest()->first();
            $lastPosSale  = PosSale::where('customer_id', $customer->id)->latest()->first();

            return [
                'order_count_total' => $webCount,
                'order_count_30d'   => Order::where('user_id', $customer->id)
                                        ->where('status', 'completed')
                                        ->where('created_at', '>=', now()->subDays(30))
                                        ->count(),
                'pos_sale_count_total' => $posCount,
                'avg_order_value'   => $webCount > 0 ? (int) ($webOrders->avg('total_amount') * 100) : 0,
                'total_spent'       => (int) ($totalSpent * 100),
                'last_order_days'    => $lastWebOrder ? (int) now()->diffInDays($lastWebOrder->created_at) : 999,
                'last_pos_sale_days' => $lastPosSale ? (int) now()->diffInDays($lastPosSale->created_at) : 999,
                'channel'           => $channel,
                'account_age_days'  => (int) now()->diffInDays($customer->created_at),
                'loyalty_points_total' => LoyaltyPoint::getBalanceFor($customer->id),
            ];
        });
    }

    /**
     * Synchronise les segments automatiques pour un client.
     */
    public function syncCustomerSegments(User $customer): array
    {
        $metrics = $this->evaluateCustomer($customer);
        $activeAutoSegments = CustomerSegment::active()->automatic()->get();
        
        $added = [];
        $removed = [];

        foreach ($activeAutoSegments as $segment) {
            $isMatch = $this->checkRules($metrics, $segment->rules);
            
            $hasSegment = $customer->segments()->where('segment_id', $segment->id)->exists();

            if ($isMatch && !$hasSegment) {
                $customer->segments()->attach($segment->id, [
                    'assigned_by' => 'system',
                    'assigned_at' => now(),
                ]);
                $added[] = $segment->slug;
            } elseif (!$isMatch && $hasSegment) {
                // On ne retire que si c'était assigné par le système
                $pivot = $customer->segments()->where('segment_id', $segment->id)->first()->pivot;
                if ($pivot->assigned_by === 'system') {
                    $customer->segments()->detach($segment->id);
                    $removed[] = $segment->slug;
                }
            }
        }

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * Synchronisation globale de tous les clients.
     */
    public function syncAllCustomers(): array
    {
        $stats = ['processed' => 0, 'updated' => 0];
        
        User::where('role', 'client')->chunk(config('crm.segment_sync_batch', 100), function ($customers) use (&$stats) {
            foreach ($customers as $customer) {
                $res = $this->syncCustomerSegments($customer);
                if (!empty($res['added']) || !empty($res['removed'])) {
                    $stats['updated']++;
                }
                $stats['processed']++;
            }
        });

        // Mettre à jour les compteurs
        CustomerSegment::all()->each(function ($segment) {
            $count = DB::table('customer_segment_members')->where('segment_id', $segment->id)->count();
            $segment->update(['customers_count' => $count]);
        });

        return $stats;
    }

    /**
     * Récupère le profil CRM complet.
     */
    public function getCustomerProfile(User $customer): array
    {
        return [
            'metrics'  => $this->evaluateCustomer($customer),
            'segments' => $customer->segments()->get(),
            'tags'     => $customer->tags()->get(),
            'loyalty'  => [
                'balance' => LoyaltyPoint::getBalanceFor($customer->id),
                'level'   => app(LoyaltyService::class)->getCurrentLevel($customer),
            ],
            'summary' => [
                'web_orders' => Order::where('user_id', $customer->id)->count(),
                'pos_sales'  => PosSale::where('customer_id', $customer->id)->count(),
            ]
        ];
    }

    /**
     * Interpréteur de règles simplifié.
     */
    protected function checkRules(array $metrics, ?array $rules): bool
    {
        if (!$rules || !isset($rules['conditions'])) return false;

        foreach ($rules['conditions'] as $condition) {
            $metric   = $condition['metric'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value    = $condition['value'] ?? null;

            if (!$metric || !isset($metrics[$metric])) return false;

            $mValue = $metrics[$metric];

            switch ($operator) {
                case '>=': if (!($mValue >= $value)) return false; break;
                case '<=': if (!($mValue <= $value)) return false; break;
                case '>':  if (!($mValue > $value))  return false; break;
                case '<':  if (!($mValue < $value))  return false; break;
                case '=':  if (!($mValue == $value)) return false; break;
                case '!=': if (!($mValue != $value)) return false; break;
                default: return false;
            }
        }

        return true;
    }
}
