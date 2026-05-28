<?php

namespace App\Services\Amira;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AmiraContextService
{
    /**
     * Build context array based on user role and space.
     * space: 'client' | 'creator' | 'admin'
     */
    public function buildContext(User $user, string $space): array
    {
        return match ($space) {
            'creator' => $this->creatorContext($user),
            'admin'   => $this->adminContext($user),
            default   => $this->clientContext($user),
        };
    }

    public function buildSystemPrompt(string $space): string
    {
        $base = "Tu es Amira, l'assistante IA de Racine by Ganda. Réponds toujours en français. Sois concise (3-4 phrases max), utile, et chaleureuse. Ne révèle jamais de données sensibles (mots de passe, clés API, données bancaires d'autres utilisateurs).";

        return match ($space) {
            'creator' => $base . "\n\nTu guides les créateurs : analyse leurs ventes, explique leurs statistiques, aide à optimiser leur boutique et leurs produits. Encourage sans flatter.",
            'admin'   => $base . "\n\nTu assistes l'équipe admin : résume les métriques, signale les anomalies, aide à prendre des décisions basées sur les données. Sois analytique et précise.",
            default   => $base . "\n\nTu aides les clients : explique les commandes, les livraisons, les produits, les retours. Oriente vers les créateurs pertinents. Stimule la découverte.",
        };
    }

    private function clientContext(User $user): array
    {
        $orders = DB::table('orders')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'status', 'total_amount', 'created_at'])
            ->toArray();

        return [
            'user_name'      => $user->name,
            'recent_orders'  => array_map(fn($o) => [
                'id'     => $o->id,
                'status' => $o->status,
                'total'  => $o->total_amount,
                'date'   => $o->created_at,
            ], $orders),
            'orders_count'   => count($orders),
        ];
    }

    private function creatorContext(User $user): array
    {
        $profile = DB::table('creator_profiles')->where('user_id', $user->id)->first();
        if (! $profile) {
            return ['user_name' => $user->name, 'space' => 'creator'];
        }

        $productCount = DB::table('products')->where('creator_id', $profile->id)->count();
        $totalSales   = DB::table('orders')
            ->where('creator_id', $profile->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');

        $plan = DB::table('creator_subscriptions as cs')
            ->join('creator_plans as cp', 'cs.creator_plan_id', '=', 'cp.id')
            ->where('cs.creator_id', $user->id)
            ->whereIn('cs.status', ['active', 'trialing'])
            ->select('cp.name', 'cp.code')
            ->first();

        return [
            'user_name'     => $user->name,
            'brand_name'    => $profile->brand_name ?? '',
            'products_count'=> $productCount,
            'total_sales'   => (float) $totalSales,
            'plan'          => $plan ? $plan->name : 'Aucun plan actif',
        ];
    }

    private function adminContext(User $user): array
    {
        $totalRevenue = DB::table('orders')
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');

        $activeCreators = DB::table('creator_profiles')
            ->where('status', 'active')
            ->count();

        $pendingOrders = DB::table('orders')
            ->where('status', 'pending')
            ->count();

        return [
            'user_name'       => $user->name,
            'total_revenue'   => (float) $totalRevenue,
            'active_creators' => $activeCreators,
            'pending_orders'  => $pendingOrders,
        ];
    }
}
