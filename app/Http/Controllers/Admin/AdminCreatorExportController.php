<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCreatorExportController extends Controller
{
    /**
     * Exporter la liste des créateurs en CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = CreatorProfile::with('user')
            ->withCount('products');

        // Appliquer les mêmes filtres que la page index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        $creators = $query->get();

        $filename = 'creators_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($creators) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Nom de la marque',
                'Nom complet',
                'Email',
                'Téléphone',
                'Statut',
                'Vérifié',
                'Actif',
                'Produits',
                'Documents',
                'Score global',
                'Date d\'inscription',
            ]);

            // Données
            foreach ($creators as $creator) {
                fputcsv($file, [
                    $creator->id,
                    $creator->brand_name,
                    $creator->user->name,
                    $creator->user->email,
                    $creator->user->phone ?? '',
                    $creator->status,
                    $creator->is_verified ? 'Oui' : 'Non',
                    $creator->is_active ? 'Oui' : 'Non',
                    $creator->products_count,
                    $creator->documents()->count(),
                    $creator->overall_score ?? 'N/A',
                    $creator->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Générer un rapport de validation.
     */
    public function validationReport(Request $request)
    {
        $stats = [
            'total' => CreatorProfile::count(),
            'pending' => CreatorProfile::where('status', 'pending')->count(),
            'active' => CreatorProfile::where('status', 'active')->where('is_active', true)->count(),
            'suspended' => CreatorProfile::where('status', 'suspended')->count(),
            'verified' => CreatorProfile::where('is_verified', true)->count(),
            'unverified' => CreatorProfile::where('is_verified', false)->count(),
            'with_documents' => CreatorProfile::whereHas('documents')->count(),
            'without_documents' => CreatorProfile::whereDoesntHave('documents')->count(),
            'avg_completion' => DB::table('creator_validation_checklists')
                ->selectRaw('AVG(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) * 100 as avg')
                ->value('avg') ?? 0,
        ];

        return view('admin.creators.reports.validation', compact('stats'));
    }
}

