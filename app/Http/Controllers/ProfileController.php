<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:500',
        ]);

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])->withInput();
        }

        if (empty(trim($request->password))) {
            return back()->withErrors(['password' => 'Le mot de passe ne peut pas être vide.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('profile.show')->with('success_password', 'Mot de passe mis à jour.');
    }
}
