<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderBy('name')->paginate(25);
        return view('uzivatele.index', compact('users'));
    }

    public function create(): View
    {
        return view('uzivatele.create', ['roles' => User::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:200', Rule::unique('users', 'email')],
            'role'     => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('uzivatele.index')->with('status', 'Uživatel přidán.');
    }

    public function edit(User $uzivatele): View
    {
        return view('uzivatele.edit', ['user' => $uzivatele, 'roles' => User::ROLES]);
    }

    public function update(Request $request, User $uzivatele): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($uzivatele->id)],
            'role'     => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $uzivatele->update($data);
        return redirect()->route('uzivatele.index')->with('status', 'Uživatel upraven.');
    }

    public function destroy(Request $request, User $uzivatele): RedirectResponse
    {
        if ($uzivatele->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Nemůžeš smazat svůj vlastní účet.']);
        }
        $uzivatele->delete();
        return redirect()->route('uzivatele.index')->with('status', 'Uživatel smazán.');
    }
}
