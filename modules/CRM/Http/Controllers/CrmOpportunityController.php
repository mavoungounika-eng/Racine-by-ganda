<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CrmOpportunity;
use Modules\CRM\Models\CrmContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmOpportunityController extends Controller
{
    /**
     * Affiche la liste des opportunités
     */
    public function index(Request $request)
    {
        $query = CrmOpportunity::with('contact');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        $opportunities = $query->orderBy('expected_close_date', 'asc')->paginate(20);

        $stats = [
            'total' => CrmOpportunity::count(),
            'open' => CrmOpportunity::whereNotIn('stage', ['won', 'lost'])->count(),
            'won' => CrmOpportunity::where('stage', 'won')->count(),
            'lost' => CrmOpportunity::where('stage', 'lost')->count(),
            'pipeline_value' => CrmOpportunity::whereNotIn('stage', ['won', 'lost'])->sum('value'),
        ];

        return view('crm::opportunities.index', compact('opportunities', 'stats'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $contacts = CrmContact::orderBy('first_name')->get();
        return view('crm::opportunities.create', compact('contacts'));
    }

    /**
     * Enregistre une nouvelle opportunité
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:crm_contacts,id',
            'title' => 'required|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stage' => 'required|in:prospection,qualification,proposition,negotiation,won,lost',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['currency'] = $validated['currency'] ?? 'FCFA';

        CrmOpportunity::create($validated);

        return redirect()->route('crm.opportunities.index')
            ->with('success', 'Opportunité créée avec succès !');
    }

    /**
     * Affiche une opportunité
     */
    public function show(CrmOpportunity $opportunite)
    {
        $opportunite->load('contact');
        return view('crm::opportunities.show', compact('opportunite'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(CrmOpportunity $opportunite)
    {
        $contacts = CrmContact::orderBy('first_name')->get();
        return view('crm::opportunities.edit', compact('opportunite', 'contacts'));
    }

    /**
     * Met à jour une opportunité
     */
    public function update(Request $request, CrmOpportunity $opportunite)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:crm_contacts,id',
            'title' => 'required|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stage' => 'required|in:prospection,qualification,proposition,negotiation,won,lost',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $opportunite->update($validated);

        return redirect()->route('crm.opportunities.index')
            ->with('success', 'Opportunité mise à jour !');
    }

    /**
     * Supprime une opportunité
     */
    public function destroy(CrmOpportunity $opportunite)
    {
        $opportunite->delete();

        return redirect()->route('crm.opportunities.index')
            ->with('success', 'Opportunité supprimée !');
    }
}

