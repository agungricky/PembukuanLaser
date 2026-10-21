<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $name = $request->input('name');
        $password = $request->input('password');
        $throttleKey = mb_strtolower($name).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'name' => 'Terlalu banyak percobaan login. Silakan coba beberapa saat lagi.',
            ]);
        }

        $users = User::where('name', $name)
            ->orderBy('id')
            ->get();

        $userLogin = null;

        foreach ($users as $user) {

            if (Hash::check($password, $user->password)) {
                $userLogin = $user;
                break;
            }
        }

        if (! $userLogin) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'name' => 'Nama atau password salah.',
            ])->onlyInput('name');
        }

        Auth::login($userLogin);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);
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
