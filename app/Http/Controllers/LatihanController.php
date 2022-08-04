<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Ujian;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LatihanController extends Controller
{
    //

    public function listLatihanByKelasForSiswa(Request $request, $id){
        $user = Auth::user();
        $siswa = $user->siswa;

        $kelas = Kelas::find($id);

        // if (!($kelas && !($siswa->kelas->where('id',$id)->pivot->is_waiting ?? true) ))
        if (!$kelas || $siswa->kelas->firstWhere('id',$id)?->pivot->is_waiting !== 0)
            return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $latihans = $kelas
            ->ujians()
            ->where('isUjian',false)
            ->get()
            ->map(fn ($ujian) => [
                'id' => $ujian->id,
                'name' => $ujian->name,
            ]);
        

        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($latihans->paginate($perPage));
        return response()->json($latihans);
    }

    public function getLatihanById(Request $request, $id){
        $user = Auth::user();
        $siswa = $user->siswa;

        $ujian = Ujian::find($id);
        
        if (!$ujian || $ujian->isUjian || !$ujian->kelas()->get()->first(fn($kelas) => $kelas->siswas()->where('id',$siswa->id)->exists()))
            return response()->json(['message' => 'Latihan tidak ditemukan'], 404);

        $soals = $ujian->soals
            ->map(fn($soal) => [
                'type' => $soal->type,
                'soal' => $soal->soal,
                'soal_id' => $soal->id,
                'jawabans' => $soal->answers,
            ]);
        
        return response()->json([
            'name' => $ujian->name,
            'data' => $soals,
        ]);
    }

    public function submit(Request $request){
        $user = Auth::user();

        $siswa = $user->siswa;
        if (!$siswa) return abort(403);

        $ujian = Ujian::findOrFail($request->id);

        return response()->json($request->answers);
    }
}
