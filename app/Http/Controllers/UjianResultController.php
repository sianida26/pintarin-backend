<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Soal;
use App\Models\Kelas;
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

    public function getByUjianId(Request $request, $id){

        $user = Auth::user();        
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $ujian = Ujian::findOrFail($id);

        $ujianResults = $ujian->ujianResults;
        $kelas = $ujian->kelas->map(function($kelas) use ($ujianResults){
            $siswas = $kelas->siswas()->wherePivot('is_waiting',false)->get();
            $result = $siswas->reduce(function($carry, $siswa) use ($ujianResults){
                $ujianResult = $ujianResults->firstWhere('siswa_id',$siswa->id);
                if (!$ujianResult) return $carry;
                if ($ujianResult->nilai === null){
                    return [$carry[0],$carry[1]+1];
                }
                return [$carry[0]+1,$carry[1]+1];
            }, [0,0]); //index 0 : n_siswa yang sudah dinilai, index 1: n_siswa yang sudah submit

            $status = "UNDEFINED STATUS";
            $message = "UNDEFINED MESSAGE";
    
            if ($result[1] === 0){
                $status = "EMPTY";
                $message = "Belum ada hasil asesmen yang masuk";
            }
            else if ($result[0] === 0 ){
                $status = "INCOMPLETE";
                $message = "Belum ada siswa yang dinilai";
            }
            else if ($result[0] === $result[1]){
                $status = "COMPLETED";
                $message = "Sudah dinilai semua";
            } else {
                $status = "PROGRESS";
                $message = $result[0] . "/" . $result[1] . " siswa sudah dinilai";
            }
    
            return [
                'id' => $kelas->id,
                'name' => $kelas->name,
                'status' => [
                    'status' => $status,
                    'message' => $message
                ],
            ];
        })
        ->values();
        
        $perPage = $request->query('perPage') ?? 10;
        if ($request->query('page')) return response()->json($kelas->paginate($perPage));
        return response()->json($kelas);
    }

    public function getByKelasId(Request $request, $ujianId, $kelasId){

        $user = Auth::user();        
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $kelas = Kelas::findOrFail($kelasId);
        $ujian = Ujian::findOrFail($ujianId);

        $siswas = $kelas->siswas()->wherePivot('is_waiting', false)->get()
            ->map(function($siswa) use ($ujianId){
                $score = null;
                $submitTime = null;
                $status = "NOT SUBMIT";
                $ujianResult = UjianResult::where('ujian_id',$ujianId)->firstWhere('siswa_id',$siswa->id);
                if ($ujianResult){
                    $score = $ujianResult->nilai;
                    $submitTime = $ujianResult->created_at;
                    $status = $score === null ? "NOT REVIEWED" : "REVIEWED";
                }
                return [
                    'id' => $siswa->id,
                    'name' => $siswa->user->name,
                    'submitAt' => $submitTime,
                    'status' => [
                        'status' => $status,
                        'score' => $score,
                    ]
                ];
            });

        
        $perPage = $request->query('perPage') ?? 10;
        if ($request->query('page')) return response()->json($siswas->paginate($perPage));
        return response()->json($siswas);
    }

    public function getById(Request $request, $id){
        $user = Auth::user();
        $guru = $user->guru;

        $ujianResult = UjianResult::findOrFail($id);
        $ujian = $ujianResult->ujian;

        $siswa = $ujianResult->siswa;
        
        if (!$ujian || !$ujian->isUjian || $ujian->guru->id !== $guru->id)
            return response()->json(['message' => 'Ujian tidak ditemukan'], 404);

        Debugbar::info($ujianResult->getAnswerBySoalId(8)->answer);
        Debugbar::info(collect($ujianResult->answers));

        $soals = $ujian->soals
            ->map(function($soal)use($ujianResult){Debugbar::info($soal->id); return[
                'type' => $soal->type,
                'soal' => $soal->soal,
                'soal_id' => $soal->id,
                'jawabans' => $soal->answers,
                'jawabanSiswa' => $ujianResult->getAnswerBySoalId($soal->id)->answer,
                'score' => $ujianResult->getAnswerBySoalId($soal->id)->score,
            ];})
            ->sortBy(function($soal){
                switch ($soal['type']){
                    case "pg": return 0;
                    case "pgk": return 1;
                    case "menjodohkan": return 2;
                    case "isian": return 3;
                    case "uraian": return 4;
                    default: return 5;
                }
            })
            ->values()
            ->all();
        
        return response()->json([
            'name' => $ujian->name,
            'siswaName' => $siswa->user->name,
            'nis' => $siswa->nis,
            'submittedAt' => $ujianResult->created_at,
            'data' => $soals,
        ]);
    }
}
