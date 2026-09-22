<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin
                            {email : The email of the user to promote}
                            {--password= : Password used when the user does not exist yet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the super admin role to a user (creates the user if it does not exist)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = $this->option('password') ?: Str::random(16);

            $user = User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            if (! $this->option('password')) {
                $this->warn("User created with a random password: {$password}");
                $this->warn('Change it after the first login.');
            }
        }

        $user->assignRole(config('roles.super_admin_role'));

        $this->info("{$email} is now a super admin (".config('roles.super_admin_role').').');

        return self::SUCCESS;
    }
}
