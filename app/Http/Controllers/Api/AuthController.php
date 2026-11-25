<?php
namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:160'],
            'nim' => ['required', 'string', 'min:4', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:getdone_users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'university' => ['nullable', 'string', 'max:200'],
        ]);
        $data['full_name'] = trim($data['full_name']);
        $data['nim'] = trim($data['nim']);
        $data['email'] = mb_strtolower(trim($data['email']));
        $user = User::create($data);
        $token = $user->createToken('getdone-web')->plainTextToken;
        return $this->ok(['access_token' => $token, 'token_type' => 'Bearer', 'user' => $user], 'Akun berhasil dibuat.', 201);
    }
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Email atau kata sandi tidak sesuai.']]);
        }
        $token = $user->createToken('getdone-web')->plainTextToken;
        return $this->ok(['access_token' => $token, 'token_type' => 'Bearer', 'user' => $user], 'Login berhasil.');
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return $this->ok(null, 'Logout berhasil.');
    }
}
