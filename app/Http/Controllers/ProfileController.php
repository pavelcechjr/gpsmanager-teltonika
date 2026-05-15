<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profil.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($user->id)],
        ], [], [
            'name'  => 'jméno',
            'email' => 'email',
        ]);

        $user->update($data);

        return back()->with('status', 'Profil byl upraven.');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Nové heslo a potvrzení se neshodují.',
            'password.min'       => 'Heslo musí mít alespoň 8 znaků.',
        ], [
            'current_password' => 'současné heslo',
            'password'         => 'nové heslo',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Současné heslo není správné.']);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('status', 'Heslo bylo změněno.');
    }
}
