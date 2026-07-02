<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'wachtwoord' => 'required',
        ]);

        $gebruiker = DB::table('gebruikers')
            ->join('rollen', 'gebruikers.rol_id', '=', 'rollen.id')
            ->select('gebruikers.*', 'rollen.naam as rol')
            ->where('gebruikers.email', $request->email)
            ->where('gebruikers.actief', 1)
            ->first();

        if (!$gebruiker) {
            return back()->with('error', 'Gebruiker niet gevonden of account is geblokkeerd.');
        }

        if (!Hash::check($request->wachtwoord, $gebruiker->wachtwoord)) {
            return back()->with('error', 'E-mailadres of wachtwoord is onjuist.');
        }

        session([
            'gebruiker_id' => $gebruiker->id,
            'gebruiker_naam' => $gebruiker->naam,
            'gebruiker_rol' => $gebruiker->rol,
        ]);

        return redirect()->route('home')
            ->with('success', 'Welkom ' . $gebruiker->naam . '!');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'naam' => 'required|string|max:100',
            'email' => 'required|email|unique:gebruikers,email',
            'wachtwoord' => 'required|min:8|confirmed',
        ]);

        DB::table('gebruikers')->insert([
            'rol_id' => 3, // Klant
            'naam' => $request->naam,
            'email' => $request->email,
            'wachtwoord' => Hash::make($request->wachtwoord),
            'actief' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('login')
            ->with('success', 'Account succesvol aangemaakt. Je kunt nu inloggen.');
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('home')
            ->with('success', 'Je bent succesvol uitgelogd.');
    }
}
