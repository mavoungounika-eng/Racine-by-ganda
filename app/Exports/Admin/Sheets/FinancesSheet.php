<?php

namespace App\Exports\Admin\Sheets;

use App\Models\Order;
use App\Models\CreatorSubscription;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancesSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Finances';
    }

    public function collection(): Collection
    {
        $rows = collect();

        // Revenus par mois (12 derniers mois)
        $monthly = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subYear())
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as mois, COUNT(*) as nb_commandes, SUM(total_amount) as revenu')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('mois')
            ->get();

        foreach ($monthly as $row) {
            $rows->push([
                $row->mois,
                $row->nb_commandes,
                number_format($row->revenu, 0, '.', ''),
                '',
                '',
            ]);
        }

        // Totaux globaux
        $totalRevenu = Order::where('payment_status', 'paid')->sum('total_amount');
        $totalAbonnements = CreatorSubscription::where('status', 'active')->count();
        $revenusAbonnements = CreatorSubscription::where('status', 'active')
            ->join('creator_plans', 'creator_subscriptions.creator_plan_id', '=', 'creator_plans.id')
            ->sum('creator_plans.price');

        $rows->push(['---', '---', '---', '---', '---']);
        $rows->push(['TOTAL revenu commandes', '', number_format($totalRevenu, 0, '.', ''), '', '']);
        $rows->push(['Abonnements actifs', $totalAbonnements, '', '', '']);
        $rows->push(['Revenus abonnements mensuel', '', number_format($revenusAbonnements, 0, '.', ''), '', '']);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'periode',
            'nb_commandes',
            'revenu_fcfa',
            'reserve1',
            'reserve2',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
