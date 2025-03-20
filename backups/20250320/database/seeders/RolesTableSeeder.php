<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'admin', 'description' => 'Amministratore']);
        Role::create(['name' => 'manager', 'description' => 'Manager']);
        Role::create(['name' => 'user', 'description' => 'Utente standard']);
    }
}
