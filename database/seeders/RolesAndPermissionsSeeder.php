<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'annonces.creer',
            'annonces.modifier-propres',
            'annonces.moderer',
            'utilisateurs.gerer',
            'tarifs.gerer',
            'paiements.voir',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        RoleModel::findOrCreate(Role::Admin->value)->syncPermissions(Permission::all());
        RoleModel::findOrCreate(Role::Moderateur->value)->syncPermissions(['annonces.moderer']);
        RoleModel::findOrCreate(Role::Bailleur->value)->syncPermissions(['annonces.creer', 'annonces.modifier-propres']);
        RoleModel::findOrCreate(Role::Client->value);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            $admin = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Administrateur',
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );
            $admin->syncRoles([Role::Admin->value]);
        }
    }
}
