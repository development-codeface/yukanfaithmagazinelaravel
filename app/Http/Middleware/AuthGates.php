<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        Log::info('✅ AuthGates middleware triggered');

        $user = Auth::user();
        Log::info('Logged-in user', ['id' => $user?->id]);

        if ($user) {
            $roles = Role::with('permissions')->get();

            $permissionsArray = [];

            foreach ($roles as $role) {
                foreach ($role->permissions as $permission) {
                    $permissionsArray[$permission->title][] = $role->id;
                }
            }

            foreach ($permissionsArray as $title => $roleIds) {
                Gate::define($title, function (User $user) use ($roleIds) {
                    return count(array_intersect(
                        $user->roles->pluck('id')->toArray(),
                        $roleIds
                    )) > 0;
                });
            }
        }

        return $next($request);
    }
}
