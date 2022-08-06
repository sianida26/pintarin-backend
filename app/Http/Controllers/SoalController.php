<?php

namespace App\Http\Controllers;

use App\Models\Soal;
use App\Models\Ujian;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SoalController extends Controller
{
    //
    public function createSoal(Request $request){

        //TODO: Buat middleware guru

        $rules = [
            'ujianId' => 'required|exists:ujians,id',
            'bobot' => 'required|numeric|min:0',
            'soal' => 'required',
            'type' => ['required', Rule::in(['pg','pgk','menjodohkan','isian','uraian'])],
            'jawabans' => 'required',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'exists' => 'Ujian tidak tersedia',
            'numeric' => 'Harus berupa angka',
            'min' => 'Harus lebih dari :min',
            'in' => 'Nilai tidak sesuai',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $ujian = Ujian::findOrFail($request->ujianId);
        if ($ujian->guru->user->id !== Auth::id()) return response()->json(['message' => 'Unauthorized'], 403);

        //Case: Pilihan ganda (pg)
        if ($request->type === 'pg'){
            $validatorPg = Validator::make($request->all(), [
                'jawabans' => 'array:content,isCorrect',
                'jawabans.*.content' => 'required',
                'jawabans.*.isCorrect' => 'required|boolean',
            ],['required' => 'Harus diisi', 'boolean' => "Harus berupa boolean"]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
            }

            $jawabans = collect($request->jawabans)
                ->map(fn($jawaban,$index) => [
                    'id' => $index,
                    'content' => $jawaban['content'],
                    'isCorrect' => $jawaban['isCorrect'],
                ]);

            if (!$jawabans->contains('isCorrect',true)) return response()->json(['errors' => [ 'jawabans' => ['Setidaknya harus ada 1 jawaban yang benar'] ], 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);

            Soal::create([
                'soal' => $request->soal,
                'bobot' => $request->bobot,
                'type' => 'pg',
                'answers' => $jawabans,
                'ujian_id' => $request->ujianId,
            ]);

            return response()->json(['message' => 'Soal berhasil dibuat']);
        }
    }

    public function uploadImage(Request $request){

        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) return abort(403);

        $url = $request->file('file')->store('soal');

        return response()->json(['url' => $url]);
    }
}