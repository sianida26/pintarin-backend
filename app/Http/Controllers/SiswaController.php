<?php

namespace App\Http\Controllers;

use App\Models\Siswa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiswaController extends Controller
{
    //
    public function all(Request $request){
        $user = Auth::user();

        if (!$user->hasRole('guru')) return abort(403);
        $guru = $user->guru;

        $siswas = Siswa::all()
            ->map(fn($siswa) => [
                'id' => $siswa->id,
                'name' => $siswa->user->name,
                'nis' => $siswa->nis ?? "-",
            ]);
        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($siswas->paginate($perPage));
        return response()->json($siswas);
    }
}
