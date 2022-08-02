<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Siswa;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasRole('guru')) return abort(403);

        $kelases = Auth::user()->guru->kelas;
        $perPage = $request->query('perPage') ?? 10;
        
        $kelases = $kelases->map(function($kelas){
            $kelas->enrollLink = env('FRONTEND_HOST') . '/' . 'enroll/' . $kelas->getEnrollToken();
            return $kelas;
        });

        if ($request->query('page')) return response()->json($kelases->paginate($perPage));
        return response()->json($kelases);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('guru')) return abort(403);
        if (Auth::user()->hasRole('guru komunitas') && $request->matpelId !== Matpel::firstWhere('name','Kimia')->id) 
            return response()->json(['message' => 'Anda hanya bisa membuat kelas mata kuliah kimia saja'], 403);

        $rules = [
            'name' => 'required|max:255',
            'matpelId' => 'required|exists:matpels,id',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'max' => 'Maksimal :max karakter',
            'exists' => 'Mata pelajaran tidak ada atau tidak valid',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $ujian = Kelas::create([
            'name' => $request->name,
            'matpel_id' => $request->matpelId,
            'guru_id' => Auth::user()->guru->id,
        ]);

        return response()->json([ 'message' => 'Kelas berhasil dibuat' ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {

        if (!Auth::user()->hasRole('guru')) return abort(403);

        $kelas = Kelas::find($id);

        if (!$kelas) return response()->json([ 'message' => 'Kelas tidak ditemukan'],404);

        if ($kelas->guru->id !== Auth::user()->guru->id)
            return response()->json([ 'message' => 'Anda hanya dapat melihat detail kelas Anda sendiri'],403);

        return response()->json([
            'name' => $kelas->name,
            'mapel' => $kelas->matpel->name,
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

    public function enroll(Request $request){
        $user = Auth::user();
        if (!$user->hasRole('siswa')) return abort(403);
        $siswa = $user->siswa;

        $kelas = null;
        try {
            $id = Crypt::decryptString(Str::before($request->token, '-'));
            $kelas = Kelas::findOrFail($id);
        } catch (DecryptException $e){
            return response()->json(['message' => 'Token tidak valid'],404);
        }

        if ($siswa->kelas()->where('kelas_id',$kelas->id)->exists())
            return response()->json(['message' => 'Kelas sudah ter-enrol'],403);

        //enroll
        $siswa->kelas()->attach($kelas, ['is_waiting' => true]);
        return response()->json(['message' => 'Berhasil enroll kelas']);
    }

    public function addSiswa(Request $request){
        $user = Auth::user();
        if (!$user->hasRole('guru')) return abort(403);

        $guru = $user->guru;
        $kelas = Kelas::find($request->kelas_id);
        $siswa = Siswa::find($request->siswa_id);
        
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'],404);
        if (!$siswa) return response()->json(['message' => 'Siswa tidak ditemukan'],404);

        if ($kelas->guru->id !== $guru->id) 
            return response()->json(['message' => 'Anda tidak dapat mengubah kelas guru lain'], 403);

        $siswaKelas = $kelas->siswas()->firstWhere('siswa_id', $siswa->id);
        if ($siswaKelas)
            return $siswaKelas->pivot->is_waiting ? response()->json(['message' => 'Siswa telah berada di daftar siswa yang mengajukan kelas'], 403)
            : response()->json(['message' => 'Siswa telah ditambahkan sebelumnya'], 403);

        $siswa->kelas()->attach($kelas, ['is_waiting' => false]);
        return response()->json(['message' => 'Berhasil menambahkan siswa'], 201);
    }

    public function getUjians(Request $request, $id){
        $user = Auth::user();

        if (!$user->hasRole('guru')) return abort(403);
        $guru = $user->guru;

        $kelas = Kelas::findOrFail($id);

        $ujians = $kelas->ujians;
        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($ujians->paginate($perPage));
        return response()->json($ujians);
    }

    public function getSiswa(Request $request, $id){
        $user = Auth::user();

        if (!$user->hasRole('guru')) return abort(403);
        $guru = $user->guru;

        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $siswas = $kelas->siswas()->wherePivot('is_waiting',false)->get();
        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($siswas->paginate($perPage));
        return response()->json($siswas);
    }

    public function getWaitingSiswa(Request $request, $id){
        $user = Auth::user();

        if (!$user->hasRole('guru')) return abort(403);
        $guru = $user->guru;

        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $siswas = $kelas->siswas()->wherePivot('is_waiting',true)->get();
        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($siswas->paginate($perPage));
        return response()->json($siswas);
    }

    public function listKelasForSiswa(Request $request){
        $user = Auth::user();

        $siswa = $user->siswa;
        $kelas = $siswa
            ->kelas()
            ->wherePivot('is_waiting', false)
            ->get()
            ->map(fn ($kelas) => [
                'kelas_id' => $kelas->id,
                'matpel_name' => $kelas->matpel->name,
                'kelas_name' => $kelas->name,
                'guru_name' => $kelas->guru->user->name,
            ]);

        $perPage = $request->query('perPage') ?? 10;

        if ($request->query('page')) return response()->json($kelas->paginate($perPage));
        return response()->json($kelas);
    }

    public function acceptSiswa(Request $request){
        $user = Auth::user();
        $guru = $user->guru;
        
        if (!$user->hasRole('guru') || !$guru) return abort(403);

        $kelas = Kelas::find($request->kelas_id);
        $siswa = Siswa::find($request->siswa_id);
        
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'],404);
        if (!$siswa) return response()->json(['message' => 'Siswa tidak ditemukan'],404);

        if ($kelas->guru->id !== $guru->id) 
            return response()->json(['message' => 'Anda tidak dapat mengubah kelas guru lain'], 403);

        $siswaKelas = $kelas->siswas()->firstWhere('siswa_id', $siswa->id);
        if (!$siswaKelas)
            return response()->json(['message' => 'Siswa tidak berada di daftar siswa yang mengajukan kelas'], 404);

        if (!$siswaKelas->pivot->is_waiting)
            return response()->json(['message' => 'Siswa telah masuk di dalam kelas'], 403);
        
        $siswa->kelas()->updateExistingPivot($kelas->id, ['is_waiting' => false]);
        return response()->json(['message' => 'Berhasil menerima siswa'], 200);
    }

    public function declineSiswa(Request $request){
        $user = Auth::user();
        $guru = $user->guru;
        
        if (!$user->hasRole('guru') || !$guru) return abort(403);

        $kelas = Kelas::find($request->kelas_id);
        $siswa = Siswa::find($request->siswa_id);
        
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'],404);
        if (!$siswa) return response()->json(['message' => 'Siswa tidak ditemukan'],404);

        if ($kelas->guru->id !== $guru->id) 
            return response()->json(['message' => 'Anda tidak dapat mengubah kelas guru lain'], 403);

        $siswaKelas = $kelas->siswas()->firstWhere('siswa_id', $siswa->id);
        if (!$siswaKelas)
            return response()->json(['message' => 'Siswa tidak berada di daftar siswa yang mengajukan kelas'], 404);

        if (!$siswaKelas->pivot->is_waiting)
            return response()->json(['message' => 'Siswa telah masuk di dalam kelas'], 403);
        
        $siswa->kelas()->detach($kelas->id);
        return response()->json(['message' => 'Berhasil menolak siswa'], 200);
    }
}
