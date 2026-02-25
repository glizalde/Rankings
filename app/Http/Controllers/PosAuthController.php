<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;



class PosAuthController extends Controller
{
    // Formulario login
    public function showLogin()
    {

        return view('posicionamiento.login');
    }

    // Procesar login
    public function login(Request $request)
    {
        $request->validate([
            'numero_economico' => 'required|numeric',
            'password' => 'required'
        ]);

        $user = User::where('numero_economico', $request->numero_economico)->first();
        $credentials = [
            'numero_economico' => $request->numero_economico,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return back()->with('error', 'Número económico o contraseña incorrectos');
        }

        session(['pos_user' => $user->id]);

        return redirect()->route('pos.dashboard');
    }

    // Dashboard protegido
    public function dashboard()
    {
        $user = User::find(session('pos_user'));
        return view('posicionamiento.dashboard', compact('user'));
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('pos.login');
    }

    public function showRegister()
    {
        return view('posicionamiento.register');
    }


    // Guardar usuario
    public function register(Request $request)
    {
        $request->validate([
            'numero_economico' => 'required|digits:5|unique:users,numero_economico',
            'cargo' => 'required',
            'correo' => 'required|email|unique:users,correo',
            'unidad' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        User::create([
            'numero_economico' => $request->numero_economico,
            'cargo' => $request->cargo,
            'correo' => $request->correo,
            'unidad' => $request->unidad,
            'name' => $request->numero_economico,
            'email' => $request->correo,
            'password' => $request->password,
        ]);

        return redirect()->route('pos.login')->with('success', 'Usuario creado');
    }
}
