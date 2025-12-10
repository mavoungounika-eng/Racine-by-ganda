<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorProfile;
use App\Models\CreatorAdminNote;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AdminCreatorNoteController extends Controller
{
    /**
     * Ajouter une note à un créateur.
     */
    public function store(Request $request, $creatorId): RedirectResponse
    {
        $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'tags' => ['nullable', 'string'],
            'is_important' => ['boolean'],
            'is_pinned' => ['boolean'],
        ]);

        $creator = CreatorProfile::findOrFail($creatorId);

        // Traiter les tags (séparés par virgules)
        $tags = [];
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->tags));
            $tags = array_filter($tags); // Supprimer les valeurs vides
        }

        $note = CreatorAdminNote::create([
            'creator_profile_id' => $creator->id,
            'created_by' => auth()->id(),
            'note' => $request->note,
            'tags' => $tags,
            'is_important' => $request->boolean('is_important'),
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        // Enregistrer l'activité
        \App\Models\CreatorActivityLog::log(
            $creator->id,
            auth()->id(),
            'note_added',
            'Note ajoutée : ' . substr($request->note, 0, 100)
        );

        return redirect()->back()->with('success', 'Note ajoutée avec succès.');
    }

    /**
     * Mettre à jour une note.
     */
    public function update(Request $request, $noteId): RedirectResponse
    {
        $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'tags' => ['nullable', 'string'],
            'is_important' => ['boolean'],
            'is_pinned' => ['boolean'],
        ]);

        $note = CreatorAdminNote::findOrFail($noteId);

        // Traiter les tags (séparés par virgules)
        $tags = [];
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->tags));
            $tags = array_filter($tags); // Supprimer les valeurs vides
        }

        $note->update([
            'note' => $request->note,
            'tags' => $tags,
            'is_important' => $request->boolean('is_important'),
            'is_pinned' => $request->boolean('is_pinned'),
            'updated_by' => auth()->id(),
        ]);

        // Enregistrer l'activité
        \App\Models\CreatorActivityLog::log(
            $note->creator_profile_id,
            auth()->id(),
            'note_updated',
            'Note modifiée : ' . substr($request->note, 0, 100)
        );

        return redirect()->back()->with('success', 'Note mise à jour avec succès.');
    }

    /**
     * Supprimer une note.
     */
    public function destroy($noteId): RedirectResponse
    {
        $note = CreatorAdminNote::findOrFail($noteId);
        $creatorId = $note->creator_profile_id;

        // Enregistrer l'activité avant suppression
        \App\Models\CreatorActivityLog::log(
            $creatorId,
            auth()->id(),
            'note_deleted',
            'Note supprimée'
        );

        $note->delete();

        return redirect()->back()->with('success', 'Note supprimée avec succès.');
    }
}

