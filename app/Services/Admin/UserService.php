<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole($data['role']);

            return $user;
        });

        return $user;
    }

    public function update(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $user->syncRoles([$data['role']]);
        });

        return $user->fresh('roles:id,name');
    }

    public function canDelete(User $user): bool
    {
        return $user->id !== auth()->id()
            && ! $user->hasRole(config('roles.super_admin_role'));
    }
}
