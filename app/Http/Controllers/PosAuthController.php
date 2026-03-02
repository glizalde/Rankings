<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;



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
        abort_unless($user, 401);

        $year = 2026;

        $query = DB::table('submissions as s')
            ->join('rankings as r', 'r.id', '=', 's.ranking_id')
            ->join('stages as st', 'st.id', '=', 's.stage_id')
            ->join('units as u', 'u.id', '=', 's.unit_id')
            ->where('s.year', $year)
            ->select(
                's.id',
                's.status',
                'r.name as ranking_name',
                'st.name as stage_name',
                'st.order as stage_order',
                'u.name as unit_name'
            )
            ->orderBy('u.name')
            ->orderBy('r.name')
            ->orderBy('st.order');

        // Si NO es admin, filtra por su unidad
        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless($unitId, 403, 'Tu usuario no tiene unidad asignada.');
            $query->where('s.unit_id', $unitId);
        }

        $submissions = $query->get();

        return view('posicionamiento.dashboard', compact('user', 'submissions', 'year'));
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
