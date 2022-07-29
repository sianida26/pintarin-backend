<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Matpel;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function show($id)
    {
        //
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
}
