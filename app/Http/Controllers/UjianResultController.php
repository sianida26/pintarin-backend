<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Soal;
use App\Models\UjianResult;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Debugbar;

class UjianResultController extends Controller
{
    //
    public function allUjian(Request $request){

        $user = Auth::user();        
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $ujians = $guru
            ->ujians
            ->where('isUjian',true)
            ->map(
                function($ujian){
                    $ujianResults = $ujian->ujianResults;
                    $kelas = $ujian->kelas->map(function($kelas) use ($ujianResults){
                        $siswas = $kelas->siswas()->wherePivot('is_waiting',false)->get();
                        return $siswas->reduce(function($carry, $siswa) use ($ujianResults){
                            $ujianResult = $ujianResults->firstWhere('siswa_id',$siswa->id);
                            if (!$ujianResult) return $carry;
                            if ($ujianResult->nilai === null){
                                return [$carry[0],$carry[1]+1];
                            }
                            return [$carry[0]+1,$carry[1]+1];
                        }, [0,0]); //index 0 : n_siswa yang sudah dinilai, index 1: n_siswa yang sudah submit
                    });

                    $result = $kelas->reduce(function($carry, $kelas){
                        if ($kelas[1] === 0) return $carry;
                        if ($kelas[0] === $kelas[1]) return [$carry[0]+1,$carry[1]+1];
                        return [$carry[0],$carry[1]+1];
                    },[0,0]); //index 1 : n_kelas yang sudah selesai dinilai, index 1: n_kelas yang sudah ada yg submit

                    $status = "UNDEFINED STATUS";
                    $message = "UNDEFINED MESSAGE";

                    if ($result[1] === 0){
                        $status = "EMPTY";
                        $message = "Belum ada hasil asesmen yang masuk";
                    }
                    else if ($result[0] === 0 ){
                        $status = "INCOMPLETE";
                        $message = "Belum ada kelas yang dinilai";
                    }
                    else if ($result[0] === $result[1]){
                        $status = "COMPLETED";
                        $message = "Sudah dinilai semua";
                    } else {
                        $status = "PROGRESS";
                        $message = $result[0] . "/" . $result[1] . " kelas sudah dinilai";
                    }

                    return [
                        'id' => $ujian->id,
                        'name' => $ujian->name,
                        'status' => [
                            'status' => $status,
                            'message' => $message
                        ],
                        '__debug__' => $kelas
                    ];
                }
            )
            ->values();
        
        $perPage = $request->query('perPage') ?? 10;
        if ($request->query('page')) return response()->json($ujians->paginate($perPage));
        return response()->json($ujians);
    }
}
