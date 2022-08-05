<?php

namespace App\Http\Controllers;

use App\Models\Ujian;

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
}
