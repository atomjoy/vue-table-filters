<?php

namespace App\Http\Controllers\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

trait UserFacets
{
    public function facets(Request $request, $guard = 'web')
    {
        $search = $request->input('search');
        $selectedRoles = $request->validated()['roles'];
        // $selectedRoles = $request->input('roles', []);

        $subQuery = User::select('id');
        if (!empty($search)) {
            $subQuery->where(fn($q) => $q->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%"));
        }

        return Role::query()
            ->where('roles.guard_name', $guard)
            // Zliczamy użytkowników przypisanych do roli, ale tylko z grupy wyszukanej
            ->withCount(['users' => fn($q) => $q->whereIn('users.id', $subQuery)])
            // Filtrujemy same modele ról (w tabeli roles) po ich nazwie
            ->when(!empty($selectedRoles), fn($query) => $query->whereIn('roles.name', $selectedRoles))
            ->get(['id', 'name'])
            ->keyBy('name')->map(fn($role) => [
                'id'    => $role->id,
                'count' => $role->users_count
            ]);
    }

    public function facetsWithSearch(Request $request, $guard = 'web')
    {
        $search = $request->input('search');
        $selectedRoles = $request->validated()['roles'];
        // $selectedRoles = $request->input('roles', []);

        $subQuery = User::select('id');
        if (!empty($search)) {
            $subQuery->where(fn($q) => $q->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%"));
        }

        return Role::query()
            ->withCount(['users' => fn($q) => $q->whereIn('users.id', $subQuery)])
            ->where('roles.guard_name', $guard)
            ->when(!empty($selectedRoles), fn($query) => $query->whereIn('roles.name', $selectedRoles))
            ->get(['id', 'name'])->map(fn($role) => [
                'value' => $role->name,
                'label' => ucfirst($role->name),
                'count' => $role->users_count,
            ]);
    }

    public function facetsWithBuilder(Builder $queryBeforePaginate, $guard = 'web')
    {
        $subQuery = clone($queryBeforePaginate ?? \App\Models\User::query());

        return Role::query()
            ->withCount(['users' => fn($q) => $q->whereIn('users.id', $subQuery->select('users.id'))])
            ->where('roles.guard_name', $guard)
            ->get(['roles.id', 'roles.name'])
            ->map(fn($role) => [
                'value' => $role->name,
                'label' => ucfirst($role->name),
                'count' => $role->users_count,
            ]);
    }
}
