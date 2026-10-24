<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Enums\TypeBailleur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\BailleurProfil;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = new User();
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'password' => Hash::make($data['password']),
            ])->save();

            $role = Role::from($data['type_compte']);
            $user->assignRole($role->value);

            if ($role === Role::Bailleur) {
                // Sans choix explicite, le bailleur est un particulier
                $type = TypeBailleur::tryFrom($data['role_bailleur'] ?? '') ?? TypeBailleur::Particulier;

                BailleurProfil::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'nom_commercial' => $type === TypeBailleur::Agence ? $data['nom_commercial'] : null,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
