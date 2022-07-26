<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    //
    public function lengkapiDataGuru(Request $request){
        
        $rules = [
            'foto' => 'file|mimes:jpg,jpeg,png',
            'nip' => 'required|numeric',
            'nuptk' => 'required|numeric',
            'jabatan' => 'required|string|max:255',
            'agama' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'statusKepegawaian' => 'required|string|max:255',
            'pendidikanTerakhir' => 'required|string|max:255',
        ];

        $messages = [
            'file' => 'Harus berupa file',
            'mimes' => 'File harus berupa gambar',
            'numeric' => 'Harus berupa numerik',
            'required' => 'Harus diisi',
            'string' => 'Harus berupa teks',
            'max' => 'Maksimal :max karakter',
            'unique' => 'Email telah terdaftar',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $guru = Auth::user()->guru;
        return response()->json($guru);
    }
}
