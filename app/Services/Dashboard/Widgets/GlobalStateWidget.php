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

        return [
            'revenue' => $this->getRevenue($today, $yesterday),
            'orders_count' => $this->getOrdersCount($today, $yesterday),
            'average_basket' => $this->getAverageBasket($today, $yesterday),
            'conversion_rate' => $this->getConversionRate($today),
            'pending_orders' => $this->getPendingOrders(),
        ];
    }

    private function getRevenue(Carbon $today, Carbon $yesterday): array
    {
        $todayRevenue = $this->orderRepository->getRevenueByDate($today);
        $yesterdayRevenue = $this->orderRepository->getRevenueByDate($yesterday);
        $avg7Days = $this->orderRepository->getAverageRevenue(7);

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

    private function getOrdersCount(Carbon $today, Carbon $yesterday): array
    {
        $todayCount = $this->orderRepository->getCountByDate($today);
        $yesterdayCount = $this->orderRepository->getCountByDate($yesterday);

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

    private function getAverageBasket(Carbon $today, Carbon $yesterday): array
    {
        $todayAvg = $this->orderRepository->getAverageBasketByDate($today);
        $yesterdayAvg = $this->orderRepository->getAverageBasketByDate($yesterday);

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

    private function getConversionRate(Carbon $today): array
    {
        $conversionRate = $this->orderRepository->getConversionRateByDate($today);
        $thresholds = config('dashboard.thresholds.conversion');

        return [
            'value' => $conversionRate,
            'formatted' => number_format($conversionRate, 1) . '%',
            'variation' => 0, // TODO: Calculer vs J-1
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
