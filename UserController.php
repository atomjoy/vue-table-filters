<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

// Attributes
// use Illuminate\Routing\Attributes\Controllers\Authorize;
// use Illuminate\Routing\Attributes\Controllers\Middleware;
// use Spatie\Permission\Middleware\RoleMiddleware;
// #[Authorize('publish articles')]
// #[Middleware(RoleMiddleware::using('manager'), only: ['index'])]
class UserController extends Controller
{
    public function index(Request $request)
    {
        // Filterable trait scope
        // $users = User::searchFilters($filters)->paginate(15);
        // $rolesStats = Role::withCount('users')
        //     ->when($request->validated()['roles'] ?? null, function ($query, $roles) {
        //         $query->whereIn('name', $roles);
        //     })->get(['id', 'name'])->keyBy('name')->map(function ($role) {
        //         return [
        //             'id'    => $role->id,
        //             'count' => $role->users_count
        //         ];
        //     });

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedColumns = ['id', 'name', 'created_at', 'email_verified_at', 'two_factor_confirmed_at'];
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'id';
        }

        $usersQuery = User::query()
            ->with('roles')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('filter_verified'), function ($query, $verified) {
                if ($verified === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($verified === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when($request->input('filter_2fa'), function ($query, $twoFactor) {
                if ($twoFactor === 'enabled') {
                    $query->whereNotNull('two_factor_confirmed_at');
                } elseif ($twoFactor === 'disabled') {
                    $query->whereNull('two_factor_confirmed_at');
                }
            });

        $usersQuery->reorder();

        if (in_array($sortBy, ['email_verified_at', 'two_factor_confirmed_at'])) {
            $usersQuery->orderByRaw("{$sortBy} IS NULL ASC")->orderBy($sortBy, $sortDir);
        } else {
            $usersQuery->orderBy($sortBy, $sortDir);
        }

        return Inertia::render('admin/users/Index', [
            'payload' => $usersQuery->paginate($perPage)->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'password' => uniqid(), // Password reset required from (reset pass form)
        ]);

        return back()->with('success', __('The user has been created. The user must reset their password.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return Inertia::render('admin/users/Show', [
            'payload' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('admin/users/Edit', [
            'payload' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        return redirect()->route('admin.users.index')->with('success', __('The user has been updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->hasRole(['admin', 'superadmin'])) {
            return back()->with('error', __('The administrator account cannot be deleted.'));
        }

        $user->delete();

        return back()->with('success', __('The user has been deleted.'));
    }

    // End-point dla masowego usuwania
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id'
        ]);

        $protected = ['admin', 'superadmin'];
        $ids = $request->input('ids');
        $usersToDelete = User::whereIn('id', $ids)->get();

        foreach ($usersToDelete as $user) {
            if ($user->hasRole($protected)) {
                return back()->with('error', 'Operation aborted. Selected users have protected roles:' . " " . implode('|', $protected));
            } else {
                $user->delete();
            }
        }

        return back()->with('success', __('Selected users successfully deleted.'));
    }
}
