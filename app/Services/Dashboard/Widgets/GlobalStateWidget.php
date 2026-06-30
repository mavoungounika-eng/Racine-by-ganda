<?php

namespace App\Services\Dashboard\Widgets;

use App\Repositories\OrderRepository;
use Carbon\Carbon;

class GlobalStateWidget
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function getData(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayStats = $this->orderRepository->getDailyStats($today);
        $yesterdayStats = $this->orderRepository->getDailyStats($yesterday);
        
        // On récupère getAverageRevenue séparément
        $avg7DaysRevenue = $this->orderRepository->getAverageRevenue(7);

        return [
            'revenue' => $this->getRevenue($todayStats['revenue'], $yesterdayStats['revenue'], $avg7DaysRevenue),
            'orders_count' => $this->getOrdersCount($todayStats['count'], $yesterdayStats['count']),
            'average_basket' => $this->getAverageBasket($todayStats['average_basket'], $yesterdayStats['average_basket']),
            'conversion_rate' => $this->getConversionRate($todayStats['conversion_rate'], $yesterdayStats['conversion_rate']),
            'pending_orders' => $this->getPendingOrders(),
        ];
    }

    private function getRevenue(float $todayRevenue, float $yesterdayRevenue, float $avg7Days): array
    {
        $variation = $yesterdayRevenue > 0 
            ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 
            : 0;

        return [
            'value' => $todayRevenue,
            'formatted' => number_format($todayRevenue, 0, ',', ' ') . ' FCFA',
            'variation' => round($variation, 1),
            'status' => $this->getRevenueStatus($todayRevenue, $avg7Days),
        ];
    }

    private function getOrdersCount(int $todayCount, int $yesterdayCount): array
    {
        $variation = $yesterdayCount > 0 
            ? (($todayCount - $yesterdayCount) / $yesterdayCount) * 100 
            : 0;

        $thresholds = config('dashboard.thresholds.orders');

        return [
            'value' => $todayCount,
            'formatted' => $todayCount . ' commande' . ($todayCount > 1 ? 's' : ''),
            'variation' => round($variation, 1),
            'status' => $this->getOrdersStatus($todayCount, $thresholds),
        ];
    }

    private function getAverageBasket(float $todayAvg, float $yesterdayAvg): array
    {
        $variation = $yesterdayAvg > 0 
            ? (($todayAvg - $yesterdayAvg) / $yesterdayAvg) * 100 
            : 0;

        return [
            'value' => $todayAvg,
            'formatted' => number_format($todayAvg, 0, ',', ' ') . ' FCFA',
            'variation' => round($variation, 1),
            'status' => 'neutral',
        ];
    }

    private function getConversionRate(float $conversionRate, float $yesterdayRate): array
    {
        $thresholds = config('dashboard.thresholds.conversion');

        $variation = $yesterdayRate > 0 
            ? (($conversionRate - $yesterdayRate) / $yesterdayRate) * 100 
            : 0;

        return [
            'value' => $conversionRate,
            'formatted' => number_format($conversionRate, 1) . '%',
            'variation' => round($variation, 1),
            'status' => $this->getConversionStatus($conversionRate, $thresholds),
        ];
    }

    private function getPendingOrders(): array
    {
        $count = $this->orderRepository->getPendingOrdersCount();
        $thresholds = config('dashboard.thresholds.pending_orders');

        return [
            'value' => $count,
            'formatted' => $count . ' commande' . ($count > 1 ? 's' : ''),
            'status' => $this->getPendingStatus($count, $thresholds),
        ];
    }

    private function getRevenueStatus(float $value, float $avg7Days): string
    {
        $thresholds = config('dashboard.thresholds.revenue');
        
        if ($value >= $avg7Days * $thresholds['good']) return 'green';
        if ($value >= $avg7Days * $thresholds['warning']) return 'orange';
        return 'red';
    }

    private function getOrdersStatus(int $value, array $thresholds): string
    {
        if ($value >= $thresholds['good']) return 'green';
        if ($value >= $thresholds['warning']) return 'orange';
        return 'red';
    }

    private function getConversionStatus(float $value, array $thresholds): string
    {
        if ($value >= $thresholds['good']) return 'green';
        if ($value >= $thresholds['warning']) return 'orange';
        return 'red';
    }

    private function getPendingStatus(int $value, array $thresholds): string
    {
        if ($value <= $thresholds['good']) return 'green';
        if ($value <= $thresholds['warning']) return 'orange';
        return 'red';
    }
}
