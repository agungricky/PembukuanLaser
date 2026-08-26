<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('name', 'password');

        if (! Auth::attempt($credentials)) {

            return back()->withErrors([
                'name' => 'Nama atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->role === 'packing') {

            return redirect()->route('packing.pesanan');

        } elseif ($user->role === 'manager' || $user->role === 'pegawai') {

            return redirect()->intended('/dashboard');

        } elseif ($user->role === 'gudang') {

            return redirect()->intended('/gudang');

        } elseif ($user->role === 'editor') {

            return redirect()->route('editor.index');

        } elseif ($user->role === 'produksi') {

            return redirect()->route('produksi.index');
            
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'name' => 'Role pengguna tidak dikenali.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
