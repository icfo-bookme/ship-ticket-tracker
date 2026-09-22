<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Convert existing is_admin users to the Admin role,
     * then drop the is_admin column.
     */
    public function up(): void
    {
        // Create the Admin role if it does not exist yet.
        $adminRole = Role::findOrCreate('Admin', 'web');

        // Convert is_admin = true users to the Admin role.
        if (Schema::hasColumn('users', 'is_admin')) {
            $users = DB::table('users')->where('is_admin', 1)->get(['id', 'name']);

            foreach ($users as $user) {
                $model = DB::table('users')->find($user->id);

                // Assign directly via pivot tables to avoid model casts.
                $exists = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('role_id', $adminRole->id)
                    ->exists();

                if (! $exists) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $adminRole->id,
                        'model_id' => $user->id,
                        'model_type' => 'App\\Models\\User',
                    ]);
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        // Give is_admin back to anyone holding the Admin role.
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');

        if ($adminRoleId) {
            $adminUserIds = DB::table('model_has_roles')
                ->where('role_id', $adminRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');

            DB::table('users')->whereIn('id', $adminUserIds)->update(['is_admin' => 1]);
        }
    }
};
