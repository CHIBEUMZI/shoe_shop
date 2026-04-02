<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','confirmed'],

            'birth_date' => ['nullable','date'],
            'address' => ['nullable','string','max:255'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),

            'role' => 'customer',
            'is_active' => true,

            'avatar' => null,
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        auth()->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $this->userResponse($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!auth()->attempt($data)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ]);
        }

        $user = $request->user();

        if (!$user->is_active) {
            auth()->logout();
            throw ValidationException::withMessages([
                'email' => ['Tài khoản đang bị khóa.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => $this->userResponse($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userResponse($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công.',
            'user' => $this->userResponse($user->fresh()),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không đúng.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'avatar' => $user->avatar,
            'birth_date' => optional($user->birth_date)->format('Y-m-d'),
            'address' => $user->address,
        ];
    }
}
