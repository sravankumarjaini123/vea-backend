<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PERMISSIONS
        DB::table('permissions')->insert([
            ['name' => 'Read', 'slug' => 'read'],
            ['name' => 'Create', 'slug' => 'create'],
            ['name' => 'Write', 'slug' => 'write'],
            ['name' => 'Delete', 'slug' => 'delete'],
        ]);

        // RESOURCES
        DB::table('resources')->insert([
            'name' => 'Users',
            'slug' => 'users',
        ]);

        // ROLES
        DB::table('roles')->insert([
            'name' => 'Super Administrator',
            'slug' => 'super-administrator',
        ]);
        DB::table('roles')->insert([
            'name' => 'Administrator',
            'slug' => 'administrator',
        ]);

        // USER - ROLES
        DB::table('users_roles')->insert([
            ['users_id' => 1, 'roles_id' => 1,],
        ]);
    }
}
