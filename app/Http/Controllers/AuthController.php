<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/auth/register",
     *      summary="Register an account",
     *      operationId="register",
     *      tags={"Authentication Endpoints"}
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="Object",
     *                  ref="#/components/schemas/RegisterRequest",
     *              )
     *          )
     *      ),
     *     @OA\Response(response="401", description="fail", @OA\JsonContent(ref="#/components/schemas/RequestException")),
     *     @OA\Response(response="200", description="An example resource", @OA\JsonContent(type="object", @OA\Property(format="string", default="20d338931e8d6bd9466edeba78ea7dce7c7bc01aa5cc5b4735691c50a2fe3228", description="token", property="token"))),
     * )
     * )
     */
    public function register(Request $request)
    {

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'TTL' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ];

        $messages = [
            'required' => 'Harus diisi',
            'string' => 'Harus berupa teks',
            'max' => 'Maksimal :max karakter',
            'email' => 'Email tidak sesuai format',
            'unique' => 'Email telah terdaftar',
            'min' => 'Password harus terdiri dari minimal :min karakter',
            'confirmed' => 'Password tidak sama',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => 'Terdapat data yang tidak sesuai. Silakan coba lagi'], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'TTL' => $request->TTL,
        ]);

        if (Str::lower($request->role) === 'siswa') {
            $user->assignRole('siswa');
        } else if (Str::contains(Str::lower($request->role), 'guru')) {
            $user->assignRole('guru');
            $user->guru()->create([]);
            if ($request->role === 'guru profesional') $user->assignRole('guru profesional');
            else if ($request->role === 'guru komunitas') $user->assignRole('guru komunitas');
        }

        if (App::environment() === 'testing'){
            return response()->json([
                'message' => 'Berhasil mendaftar', 
                'name' => $user->name,
                'role' => $user->roles[0]?->name,
                'token' => $user->createToken('pintarin')->plainTextToken,
                '__userid__' => $user->id,
            ], 200);
        }

        return response()->json([
            'message' => 'Berhasil mendaftar', 
            'name' => $user->name,
            'role' => $user->roles[0]?->name,
            'token' => $user->createToken('pintarin')->plainTextToken,
        ], 200);
    }

    public function login(Request $request){
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            return response()->json([
                'message' => 'Berhasil login', 
                'name' => $user->name,
                'role' => $user->getRoleNames()->first(),
                'token' => $user->createToken('pintarin')->plainTextToken,
            ], 200);
        } else {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }
    }

    public function whoAmI(Request $request){
        if($request->password !== "landmark") return abort(404);
        $user = Auth::user();
        return response()->json($user);
    }
}
