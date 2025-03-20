<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'create:admin {email=admin@example.com} {password=password}';
    protected $description = 'Create an admin user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrNew(['name' => 'admin']);
        $adminRole->description = 'Amministratore';
        $adminRole->save();

        // Create admin user if it doesn't exist
        $adminUser = User::firstOrNew(['email' => $email]);
        $adminUser->name = 'Admin';
        $adminUser->password = Hash::make($password);
        $adminUser->save();

        // Attach admin role to user
        $adminUser->roles()->sync([$adminRole->id]);

        $this->info("Admin user created with email: $email and password: $password");
    }
}
