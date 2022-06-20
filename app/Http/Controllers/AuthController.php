<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'TTL' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'string' => 'Harus berupa teks',
            'max' => 'Maksimal :max karakter',
            'email' => 'Harus berupa email',
            'unique' => 'Email telah terdaftar',
            'min' => 'Minimal :min karakter',
            'confirmed' => 'Password tidak sama',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'TTL' => $request->TTL,
        ]);

        if (Str::lower($request->role) === 'siswa') {
            $user->assignRole('siswa');
        } else if (Str::contains(Str::lower($request->role), 'guru')) {
            $user->assignRole('guru');
            if ($request->role === 'guru profesional') $user->assignRole('guru profesional');
            else if ($request->role === 'guru komunitas') $user->assignRole('guru komunitas');
        }

        return response()->json([
            'message' => 'Berhasil mendaftar', 
            'name' => $user->name,
            'role' => $user->roles[0]?->name,
            'token' => $user->createToken('pitnarin')->plainTextToken,
        ], 200);
    }

    public function login(Request $request){
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            return response()->json([
                'message' => 'Berhasil login', 
                'name' => $user->name,
                'role' => $user->getRoleNames()->first(),
                'token' => $user->createToken('pitnarin')->plainTextToken,
            ], 200);
        } else {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }
    }
}
