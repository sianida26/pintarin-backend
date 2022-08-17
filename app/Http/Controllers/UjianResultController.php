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
                    'id' => $ujianResult?->id,
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

        $soals = $ujian->soals
            ->map(fn ($soal) => [
                'type' => $soal->type,
                'soal' => $soal->soal,
                'soal_id' => $soal->id,
                'jawabans' => $soal->answers,
                'bobot' => $soal->bobot,
                'jawabanSiswa' => $ujianResult->getAnswerBySoalId($soal->id)->get('answer'),
                'score' => $ujianResult->getAnswerBySoalId($soal->id)->get('score'),
            ])
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

    public function submitNilai(Request $request, $id){
        $user = Auth::user();

        $guru = $user->guru;
        if (!$guru) return abort(403);

        $ujianResult = UjianResult::findOrFail($id);
        $ujian = $ujianResult->ujian;

        $bobotTotal = 0;
        $answers = collect($request->scores)->map(function($score)use(&$bobotTotal,$ujianResult){

            // dd($answer);
            
            $answer = $ujianResult->getAnswerBySoalId($score['soalId']);
            $answer['score'] = $score['score'] ?? 0;
            $bobotTotal += Soal::findOrFail($answer['id'])->bobot;
            return $answer;
        });

        $ujianResult->answers = $answers;
        $ujianResult->nilai = $answers->sum('score')/$bobotTotal*100;
        $ujianResult->save();

        return response()->json($request->answers);
    }

    public function getRaporSiswa(Request $request){
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) return abort(403);

        /**
         * Returns response body:
         * - id: ujianResult id
         * - name: ujian name
         * - score: score
         * - deskripsi: deskripsi
         * 
         * Sorted by submittedAt
         */

        $ujianResults = $siswa->ujianResults()->get()
            ->map(function($ujianResult){
                $ujian = $ujianResult->ujian;
                return [
                    'id' => $ujianResult->id,
                    'name' => $ujian->name,
                    'score' => $ujianResult->nilai,
                    'deskripsi' => $ujian->deskripsi,
                    'submittedAt' => $ujianResult->created_at,
                ];
            })
            ->sortBy('submittedAt')
            ->values()
            ->all();
        
        return response()->json($ujianResults);
    }

    public function getRaporSiswaByKelasId(Request $request, $kelasId){
        $user = Auth::user();
        $siswa = $user->siswa;
        if (!$siswa) return abort(403);
        
        $kelas = Kelas::findOrFail($kelasId);
        $ujianResults = $kelas
            ->ujians
            ->where('isUjian',true)
            ->map->ujianResults
            ->flatten()
            ->where('siswa_id',$siswa->id)
            ->map(function($ujianResult){
                $ujian = $ujianResult->ujian;

                $resultCategory = "Belum Ujian";
                if ($ujianResult->nilai === null) $resultCategory = "Belum dinilai";
                else if ($ujianResult->nilai >= 91) $resultCategory = "Mahir";
                else if ($ujianResult->nilai >= 76) $resultCategory = "Cakap";
                else if ($ujianResult->nilai >= 51) $resultCategory = "Dasar";
                else if ($ujianResult->nilai >= 0) $resultCategory = "Perlu Intervensi Khusus";

                $deskripsi = "";
                if ($ujian->category === "numerasi"){
                    if ($resultCategory === "A") $deskripsi = "Peserta didik mampu bernalar untuk menyelesaikan masalah kompleks serta nonrutin berdasarkan konsep matematika yang dimilikinya.";
                    else if ($resultCategory === "B") $deskripsi = "Peserta didik mampu mengaplikasikan pengetahuan matematika yang dimiliki dalam konteks yang lebih beragam.";
                    else if ($resultCategory === "C") $deskripsi = "Peserta didik memiliki kemampuan dasar matematika: komputasi dasar dalam bentuk persamaan langsung, konsep dasar terkait geometri dan statistika, serta menyelesaikan masalah matematika sederhana yang rutin.";
                    else if ($resultCategory === "D") $deskripsi = "Peserta didik hanya memiliki pengetahuan matematika yang terbatas (penguasaan konsep yang parsial dan keterampilan komputasi yang terbatas).";
                } else if ($ujian->category === "literasi"){
                    if ($resultCategory === "A") $deskripsi = "Peserta didik mampu mengitegrasikan beberapa informasi lintas teks; mengevaluasi isi, kualitas, cara penulisan suatu teks, dan bersikap reflektif terhadap isi teks.";
                    else if ($resultCategory === "B") $deskripsi = "Peserta didik mampu membuat interpretasi dari informasi implisit yang ada dalam teks; mampu membuat simpulan dari hasil integrasi beberapa informasi dalam suatu teks.";
                    else if ($resultCategory === "C") $deskripsi = "Peserta didik mampu menemukan dan mengambil informasi eksplisit yang ada dalam teks serta membuat interpretasi sederhana.";
                    else if ($resultCategory === "D") $deskripsi = "Peserta didik belum mampu menemukan dan mengambil informasi eksplisit yang ada dalam teks ataupun membuat interpretasi sederhana.";
                }

                return [
                    'id' => $ujianResult->id,
                    'name' => $ujian->name,
                    'score' => $ujianResult->nilai ?? "-",
                    'resultCategory' => $resultCategory,
                    'deskripsi' => $deskripsi,
                ];
            })
            ->sortBy('submittedAt')
            ->values()
            ->all();
        
        return response()->json([
            'kelas_name' => $kelas->name,
            'matpel_name' => $kelas->matpel->name,
            'guru_name' => $kelas->guru->user->name,
            'data' => $ujianResults
        ]);
    }
}
