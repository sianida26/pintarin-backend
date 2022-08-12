<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Soal;
use App\Models\UjianResult;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UjianController extends Controller
{

    public function __construct(){
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        // if (!Auth::user()->hasRole('guru')) return abort(404);

        // $ujians = Auth::user()->guru->ujians();
        // $perPage = $request->query('perPage') ?? 10;

        // if ($request->query('page')) return response()->json($ujians->simplePaginate($perPage));
        // return response()->json($ujians->get());

        //Role Guru
        $user = Auth::user();
        if ($user->hasRole('guru')){
            $guru = $user->guru;
            $ujians = $guru
                ->ujians
                ->map(
                    fn($ujian) => [
                        'id' => $ujian->id,
                        'name' => $ujian->name,
                        'category' => $ujian->category,
                        'type' => $ujian->isUjian ? 'ujian' : 'latihan',
                    ]
                );
            
            $perPage = $request->query('perPage') ?? 10;
            if ($request->query('page')) return response()->json($ujians->paginate($perPage));
            return response()->json($ujians);
        }
        else return abort(403);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $rules = [
            'name' => 'required|max:255',
            'category' => ['required',Rule::in(['literasi','numerasi'])],
            'isUjian' => 'required|boolean',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'max' => 'Maksimal :max karakter',
            'in' => 'Harus berupa literasi atau numerasi',
            'boolean' => 'Harus berupa boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $ujian = Ujian::create([
            'name' => $request->name,
            'category' => $request->category,
            'isUjian' => $request->isUjian,
            'guru_id' => $guru->id,
        ]);

        return response()->json([ 'id' => $ujian->id ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $guru = $user->guru;
        $ujian = Ujian::find($id);

        if (!$guru) return abort(403);
        if (!$ujian) return abort(404, 'Ujian tidak ditemukan');
        if ($ujian->guru->id !== $guru->id) abort(403, 'Anda tidak dapat melihat ujian orang lain!');

        $soals = $ujian->soals->map(fn($soal) => [
            'id' => $soal->id,
            'soal' => $soal->soal,
            'type' => $soal->type,
            'bobot' => $soal->bobot,
        ]);

        return response()->json([
            'id' => $ujian->id,
            'category' => $ujian->category,
            'type' => $ujian->isUjian ? "ujian" : "latihan",
            'name' => $ujian->name,
            'soals' => $soals
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function edit(Request $request, $id)
    {

        $user = Auth::user();
        $guru = $user->guru;
        $ujian = Ujian::find($id);

        abort_if(!$ujian, 404, 'Ujian tidak ditemukan');
        abort_if($ujian->guru->id !== $guru->id, 403, "Anda tidak dapat mengedit ujian orang lain!");

        $rules = [
            'name' => 'required|max:255',
            'category' => ['required',Rule::in(['literasi','numerasi'])],
            'isUjian' => 'required|boolean',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'max' => 'Maksimal :max karakter',
            'in' => 'Harus berupa literasi atau numerasi',
            'boolean' => 'Harus berupa boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $ujian->update([
            'name' => $request->name,
            'category' => $request->category,
            'isUjian' => $request->isUjian,
        ]);

        return response()->json([ 'id' => $ujian->id ]);
    }

    public function delete(Request $request, $id){

        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $ujian = Ujian::find($id);
        if (!$ujian) return abort(404, "Ujian tidak ditemukan");

        if ($ujian->guru->id !== $guru->id) return abort(403, "Anda tidak dapat menghapus ujian orang lain!");

        $ujian->delete();
        return response()->json(['message' => 'Ujian berhasil dihapus!']);
    }

    public function getUjianById(Request $request, $id){
        $user = Auth::user();
        $siswa = $user->siswa;

        $ujian = Ujian::find($id);
        
        if (!$ujian || !$ujian->isUjian || !$ujian->kelas()->get()->first(fn($kelas) => $kelas->siswas()->where('id',$siswa->id)->exists()))
            return response()->json(['message' => 'Ujian tidak ditemukan'], 404);

        $soals = $ujian->soals
            ->map(fn($soal) => [
                'type' => $soal->type,
                'soal' => $soal->soal,
                'soal_id' => $soal->id,
                'jawabans' => $soal->answers,
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
            'guru' => $ujian->guru->user->name,
            'category' => $ujian->category,
            'data' => $soals,
        ]);
    }

    public function submit(Request $request){
        $user = Auth::user();

        $siswa = $user->siswa;
        if (!$siswa) return abort(403);

        $ujian = Ujian::findOrFail($request->ujianId);
        if(!$ujian->isUjian) return abort(404);


        $answers = collect($request->answers)->map(function($answer){

            // dd($answer);
            
            $soal = Soal::findOrFail($answer['soalId']);
            $score = null;

            if ($soal->type === "pg"){
                $score = collect($soal->answers)->firstWhere('id',$answer['answer'])['isCorrect'] ?? false ? $soal->bobot : 0;
            }

            if ($soal->type === "pgk"){
                $keys = collect($soal->answers)
                    ->where('isCorrect',true)
                    ->map
                    ->isCorrect
                    ->keys();
                
                $studentAnswer = collect($answer['answer'])->sort();
                if ($keys->count() === $studentAnswer->count() && $keys->diff($studentAnswer)->isEmpty())
                    $score = $soal->bobot;
                else 
                    $score = 0;
            }
            
            return [
                'id' => $soal->id,
                'answer' => $soal->type == "pgk" ? collect($answer['answer'])->join(',') : $answer['answer'],
                'score' => $score,
            ];
        });

        UjianResult::create([
            'siswa_id' => $siswa->id,
            'ujian_id' => $ujian->id,
            'answers' => $answers,
            'nilai' => 0,
        ]);

        return response()->json($request->answers);
    }
}
