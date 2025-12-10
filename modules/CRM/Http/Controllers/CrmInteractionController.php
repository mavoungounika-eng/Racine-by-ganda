<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CrmContact;
use Modules\CRM\Models\CrmInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmInteractionController extends Controller
{
    /**
     * Affiche la liste de toutes les interactions
     */
    public function index(Request $request)
    {
        $query = CrmInteraction::with(['contact', 'user']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('contact')) {
            $query->whereHas('contact', function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->contact . '%')
                  ->orWhere('last_name', 'like', '%' . $request->contact . '%');
            });
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('summary', 'like', '%' . $request->search . '%')
                  ->orWhere('details', 'like', '%' . $request->search . '%');
            });
        }

        $interactions = $query->latest('created_at')->paginate(20);

        return view('crm::interactions.index', compact('interactions'));
    }

    /**
     * Enregistre une nouvelle interaction pour un contact
     */
    public function store(Request $request, CrmContact $contact)
    {
        $validated = $request->validate([
            'type' => 'required|in:call,email,meeting,note,other',
            'summary' => 'required|string|max:255',
            'details' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['contact_id'] = $contact->id;

        CrmInteraction::create($validated);

        return back()->with('success', 'Interaction enregistrée avec succès !');
    }

    /**
     * Supprime une interaction
     */
    public function destroy(CrmInteraction $interaction)
    {
        $interaction->delete();

        return back()->with('success', 'Interaction supprimée !');
    }
}
