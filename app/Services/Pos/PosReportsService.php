<?php

namespace App\Services\Pos;

use App\Models\PosSession;
use Illuminate\Database\Eloquent\Collection;

/**
 * PosReportsService - Rapports et analytics POS
 *
 * Fournit:
 * - KPIs ventes (total, count, moyenne)
 * - Analyse paiements par méthode
 * - Discrepancies tracking
 * - Performance caissiers
 */
class PosReportsService
{
    /**
     * Rapport journalier POS
     */
    public function getDailyReport(\DateTime $date): array
    {
        $sessions = PosSession::whereDate('opened_at', $date)
            ->where('status', 'closed')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'sessions_count' => $sessions->count(),
            'sessions_total_sales' => $sessions->sum(fn($s) => $s->sales()->sum('total_amount')),
            'total_opening_cash' => $sessions->sum('opening_cash'),
            'total_closing_cash' => $sessions->sum('closing_cash'),
            'total_expected_cash' => $sessions->sum('expected_cash'),
            'total_cash_difference' => $sessions->sum('cash_difference'),
            'discrepancies' => $sessions->filter(fn($s) => abs($s->cash_difference) >= 1.00)->count(),
            'payment_methods' => $this->analyzePaymentMethods($sessions),
            'performance' => $this->analyzeOperatorPerformance($sessions),
        ];
    }

    /**
     * Rapport période (semaine, mois)
     */
    public function getPeriodReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->get();

        $dailyData = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dailyData[$currentDate->format('Y-m-d')] = $this->getDailyReport($currentDate);
            $currentDate->addDay();
        }

        return [
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'days' => count($dailyData),
            'total_sessions' => $sessions->count(),
            'total_sales' => $sessions->sum(fn($s) => $s->sales()->sum('total_amount')),
            'average_session_sales' => $sessions->count() > 0 ? $sessions->sum(fn($s) => $s->sales()->sum('total_amount')) / $sessions->count() : 0,
            'total_cash_discrepancies' => $sessions->sum('cash_difference'),
            'discrepancy_rate' => $sessions->count() > 0 ? ($sessions->filter(fn($s) => abs($s->cash_difference) >= 1.00)->count() / $sessions->count()) * 100 : 0,
            'daily_data' => $dailyData,
        ];
    }

    /**
     * Analyser paiements par méthode
     */
    private function analyzePaymentMethods(Collection $sessions): array
    {
        $paymentStats = [];
        $paymentMethods = ['cash', 'card', 'mobile_money', 'mixed'];

        foreach ($paymentMethods as $method) {
            $sales = $sessions->flatMap(fn($s) => $s->sales)
                ->where('payment_method', $method);

            $paymentStats[$method] = [
                'count' => $sales->count(),
                'total' => $sales->sum('total_amount'),
                'average' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            ];
        }

        return $paymentStats;
    }

    /**
     * Analyser performance opérateurs
     */
    private function analyzeOperatorPerformance(Collection $sessions): array
    {
        $operatorStats = [];

        foreach ($sessions as $session) {
            $userId = $session->opened_by;
            $operator = $session->opener?->name ?? "Unknown (ID: {$userId})";

            if (!isset($operatorStats[$operator])) {
                $operatorStats[$operator] = [
                    'sessions' => 0,
                    'total_sales' => 0,
                    'discrepancies' => 0,
                    'discrepancy_total' => 0,
                ];
            }

            $operatorStats[$operator]['sessions']++;
            $operatorStats[$operator]['total_sales'] += $session->sales()->sum('total_amount');

            if (abs($session->cash_difference) >= 1.00) {
                $operatorStats[$operator]['discrepancies']++;
                $operatorStats[$operator]['discrepancy_total'] += $session->cash_difference;
            }
        }

        // Calculer discrepancy rate
        foreach ($operatorStats as &$stats) {
            $stats['discrepancy_rate'] = $stats['sessions'] > 0
                ? ($stats['discrepancies'] / $stats['sessions']) * 100
                : 0;
            $stats['avg_discrepancy'] = $stats['discrepancies'] > 0
                ? $stats['discrepancy_total'] / $stats['discrepancies']
                : 0;
        }

        return $operatorStats;
    }

    /**
     * Rapport discrepancies
     */
    public function getDiscrepancyReport(\DateTime $startDate, \DateTime $endDate, float $minThreshold = 1.00): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->whereRaw('ABS(cash_difference) >= ?', [$minThreshold])
            ->get();

        return [
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'threshold' => $minThreshold,
            'discrepancies_count' => $sessions->count(),
            'total_discrepancy_amount' => $sessions->sum('cash_difference'),
            'average_discrepancy' => $sessions->count() > 0 ? $sessions->sum('cash_difference') / $sessions->count() : 0,
            'max_discrepancy' => $sessions->max('cash_difference'),
            'min_discrepancy' => $sessions->min('cash_difference'),
            'details' => $sessions->map(function ($session) {
                return [
                    'session_id' => $session->id,
                    'machine_id' => $session->machine_id,
                    'operator' => $session->opener?->name,
                    'opened_at' => $session->opened_at,
                    'closed_at' => $session->closed_at,
                    'expected_cash' => $session->expected_cash,
                    'actual_cash' => $session->closing_cash,
                    'difference' => $session->cash_difference,
                    'notes' => $session->notes,
                ];
            })->toArray(),
        ];
    }

    /**
     * Export données brutes (CSV-ready)
     */
    public function exportSessionsData(\DateTime $startDate, \DateTime $endDate): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('opener', 'sales')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'operator_name' => $session->opener?->name,
                'opened_at' => $session->opened_at->toIso8601String(),
                'closed_at' => $session->closed_at->toIso8601String(),
                'duration_minutes' => $session->opened_at->diffInMinutes($session->closed_at),
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
                'expected_cash' => $session->expected_cash,
                'cash_difference' => $session->cash_difference,
                'sales_count' => $session->sales()->count(),
                'total_sales_amount' => $session->sales()->sum('total_amount'),
                'notes' => $session->notes,
            ];
        })->toArray();
    }
}
