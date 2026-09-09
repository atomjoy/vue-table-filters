<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

trait UserFacets
{
    public function roleFacets($guard = 'web')
    {
        return Role::query()
            ->withCount(['users'])
            ->where('roles.guard_name', $guard)
            ->get(['id', 'name'])->keyBy('name')->map(fn($role) => [
                'id'    => $role->id,
                'count' => $role->users_count
            ]);
    }

    public function roleFacetsWithSearch(Request $request, $guard = 'web')
    {
        $search = $request->input('search');

        return Role::query()
            ->withCount(['users' => function ($query) use ($search) {
                $query->when(!empty($search), function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('users.name', 'like', "%{$search}%")
                            ->orWhere('users.email', 'like', "%{$search}%");
                    });
                });
            }])
            ->where('roles.guard_name', $guard)
            ->get(['id', 'name'])->map(fn($role) => [
                'value' => $role->name,
                'label' => ucfirst($role->name),
                'count' => $role->users_count,
            ]);
    }
}
