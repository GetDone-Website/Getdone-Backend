<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends ApiController
{
    private const PROFILE_FIELDS = ['id', 'full_name', 'nim', 'email', 'university', 'avatar_url', 'onboarding_data', 'created_at'];

    public function me(Request $request)
    {
        return $this->ok($request->user()->only(self::PROFILE_FIELDS));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:160'],
            'nim' => ['nullable', 'string', 'min:4', 'max:32'],
            'university' => ['nullable', 'string', 'max:200'],
            'avatar_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update([
            'full_name' => trim($data['full_name']),
            'nim' => isset($data['nim']) && trim($data['nim']) !== '' ? trim($data['nim']) : null,
            'university' => isset($data['university']) && trim($data['university']) !== '' ? trim($data['university']) : null,
            'avatar_url' => array_key_exists('avatar_url', $data)
                ? (trim((string) $data['avatar_url']) !== '' ? trim((string) $data['avatar_url']) : null)
                : $user->avatar_url,
        ]);

        return $this->ok($user->fresh()->only(self::PROFILE_FIELDS), 'Profil berhasil diperbarui.');
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('getdone_users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak sesuai.'],
            ]);
        }

        $user->update(['email' => mb_strtolower(trim($data['email']))]);

        return $this->ok($user->fresh()->only(self::PROFILE_FIELDS), 'Email akun berhasil diperbarui.');
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $dir = public_path('uploads/avatars');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Hapus file avatar lama milik user jika berasal dari upload lokal.
        if ($user->avatar_url && str_contains($user->avatar_url, '/uploads/avatars/')) {
            $oldName = basename(parse_url($user->avatar_url, PHP_URL_PATH) ?: '');
            $oldPath = $dir . DIRECTORY_SEPARATOR . $oldName;
            if ($oldName && File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $file = $request->file('avatar');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = 'user_' . $user->id . '_' . time() . '.' . $ext;
        $file->move($dir, $filename);

        $url = $request->getSchemeAndHttpHost() . '/uploads/avatars/' . $filename;
        $user->update(['avatar_url' => $url]);

        return $this->ok($user->fresh()->only(self::PROFILE_FIELDS), 'Foto profil berhasil diperbarui.');
    }

    public function onboarding(Request $request)
    {
        $payload = $request->validate(['onboarding' => ['required', 'array'], 'onboarding.entryYear' => ['nullable','integer','min:1990','max:2200'], 'onboarding.activeSemester' => ['nullable','integer','min:1','max:100'], 'onboarding.targetSks' => ['nullable','integer','min:0','max:50'], 'onboarding.courses' => ['sometimes','array','max:100'], 'onboarding.arrears' => ['sometimes','array','max:100']]);
        $request->user()->update(['onboarding_data' => $payload['onboarding']]);
        return $this->ok($request->user()->onboarding_data, 'Konfigurasi akademik disimpan.');
    }
}
