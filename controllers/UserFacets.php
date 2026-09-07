<?php

namespace App\Http\Controllers\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

trait UserFacets
{
    public function facetsWithSearch(Request $request, $guard = 'web')
    {
        $search = $request->input('search');
        $selectedRoles = $request->validated()['roles'];
        // $selectedRoles = $request->input('roles', []);
        // $guardName = (new User())->getDefaultGuardName();

        // Podzapytanie wyszukiwarki
        $subQuery = User::select('id');
        if (!empty($search)) {
            $subQuery->where(fn($q) => $q->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%"));
        }

        return Role::query()
            // Zliczamy użytkowników przypisanych do roli, ale tylko z grupy wyszukanej
            ->withCount(['users' => fn($q) => $q->whereIn('users.id', $subQuery)])
            ->where('roles.guard_name', $guard)
            // Filtrujemy same modele ról (w tabeli roles) po ich nazwie
            ->when(!empty($selectedRoles), fn($query) => $query->whereIn('roles.name', $selectedRoles))
            ->get(['id', 'name', 'guard_name'])->keyBy('name')->map(fn($role) => [
                'id'    => $role->id,
                'count' => $role->users_count
            ]);
    }
}
