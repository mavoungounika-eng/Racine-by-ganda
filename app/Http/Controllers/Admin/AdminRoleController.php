<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRoleController extends AdminController
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): View
    {
        $query = Role::withCount('users');

        // Recherche par nom ou slug
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filtre par statut actif/inactif
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $roles = $query->paginate(15)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Role::create($validated);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update($validated);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Vérifier si le rôle est utilisé par des utilisateurs
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Ce rôle ne peut pas être supprimé car il est utilisé par ' . $role->users()->count() . ' utilisateur(s).');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rôle supprimé avec succès.');
    }
}

