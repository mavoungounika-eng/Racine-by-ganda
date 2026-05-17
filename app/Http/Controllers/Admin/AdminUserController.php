<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends AdminController
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $query = User::with('roleRelation');

        // Recherche par nom ou email
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filtre par is_admin
        if ($request->filled('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role_id', $request->get('role'));
        } elseif ($request->filled('role_id')) {
            $query->where('role_id', $request->get('role_id'));
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $users = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function dataUsers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $query = User::with('roleRelation:id,name,slug');
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->get('role'));
        }
        if ($request->filled('status') && $request->get('status') !== '') {
            $query->where('status', $request->get('status'));
        }
        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function bulkDisable(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $ids = array_filter($ids, fn ($id) => $id !== auth()->id()); // never disable self
        $count = User::whereIn('id', $ids)->update(['status' => 'inactive']);

        return response()->json(['success' => true, 'message' => $count.' utilisateur(s) désactivé(s)']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $ids = array_filter($ids, fn ($id) => $id !== auth()->id()); // never delete self
        $count = User::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count.' utilisateur(s) supprimé(s)']);
    }

    public function exportCsv(Request $request): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', User::class);
        $query = User::query();
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) { $q->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"); });
        }
        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('role'))   $query->where('role',   $request->get('role'));
        $rows = $query->orderBy('created_at','desc')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID,Nom,Email,Rôle,Statut,Inscription\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [
                $r->id,
                '"'.str_replace('"','""',$r->name).'"',
                '"'.str_replace('"','""',$r->email).'"',
                $r->role ?? '',
                $r->status ?? '',
                $r->created_at ? $r->created_at->format('Y-m-d') : '',
            ])."\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="utilisateurs_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $validated = $request->validated();
        
        // Hash du mot de passe si fourni
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Gestion du rôle par défaut (client) si non fourni
        if (empty($validated['role_id'])) {
            $clientRole = Role::where('slug', 'client')->first();
            if ($clientRole) {
                $validated['role_id'] = $clientRole->id;
            }
        }

        User::create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $validated = $request->validated();

        // Hash du mot de passe seulement s'il est fourni
        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        // Empêcher la suppression de soi-même
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }
}

