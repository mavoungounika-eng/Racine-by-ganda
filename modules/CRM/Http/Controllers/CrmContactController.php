<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CrmContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmContactController extends Controller
{
    /**
     * Affiche la liste des contacts
     */
    public function index(Request $request)
    {
        $query = CrmContact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => CrmContact::count(),
            'leads' => CrmContact::where('type', 'lead')->count(),
            'clients' => CrmContact::where('type', 'client')->count(),
            'partners' => CrmContact::where('type', 'partner')->count(),
        ];

        return view('crm::contacts.index', compact('contacts', 'stats'));
    }

    /**
     * Exporte les contacts en Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['type', 'status']);
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\CRM\Exports\ContactsExport($filters),
            'contacts_crm_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('crm::contacts.create');
    }

    /**
     * Enregistre un nouveau contact
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:lead,client,partner,supplier',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'source' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,prospect',
            'tags' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        CrmContact::create($validated);

        return redirect()->route('crm.contacts.index')
            ->with('success', 'Contact créé avec succès !');
    }

    /**
     * Affiche un contact
     */
    public function show(CrmContact $contact)
    {
        $contact->load(['interactions', 'opportunities']);
        return view('crm::contacts.show', compact('contact'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(CrmContact $contact)
    {
        return view('crm::contacts.edit', compact('contact'));
    }

    /**
     * Met à jour un contact
     */
    public function update(Request $request, CrmContact $contact)
    {
        $validated = $request->validate([
            'type' => 'required|in:lead,client,partner,supplier',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'source' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,prospect',
            'tags' => 'nullable|string',
        ]);

        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } else {
            $validated['tags'] = null;
        }

        $contact->update($validated);

        return redirect()->route('crm.contacts.index')
            ->with('success', 'Contact mis à jour !');
    }

    /**
     * Supprime un contact
     */
    public function destroy(CrmContact $contact)
    {
        $contact->delete();

        return redirect()->route('crm.contacts.index')
            ->with('success', 'Contact supprimé !');
    }
}

