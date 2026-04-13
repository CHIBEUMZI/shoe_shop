<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Step 1: Initiate registration - validate data and send verification code
     */
    public function initRegister(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        // Invalidate old verification codes for this email
        EmailVerificationCode::where('email', $data['email'])
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        // Generate new verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        EmailVerificationCode::create([
            'email' => $data['email'],
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(15),
            'user_data' => json_encode([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
                'is_active' => true,
                'avatar' => null,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]),
        ]);

        // Send verification email
        Mail::to($data['email'])->queue(
            new EmailVerificationMail($data['email'], $code, $token)
        );

        return response()->json([
            'message' => 'Mã xác nhận đã được gửi đến email của bạn.',
            'email' => $data['email'],
        ]);
    }

    /**
     * Step 2: Verify email code and complete registration
     */
    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $record = EmailVerificationCode::where('email', $data['email'])
            ->where('code', $data['code'])
            ->whereNull('verified_at')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác nhận không hợp lệ hoặc đã được sử dụng.'],
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác nhận đã hết hạn. Vui lòng yêu cầu mã mới.'],
            ]);
        }

        // Create the user
        $userData = json_decode($record->user_data, true);
        $user = User::create($userData);

        // Mark verification code as used
        $record->update(['verified_at' => now()]);

        // Auto login
        auth()->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $this->userResponse($user),
        ], 201);
    }

    /**
     * Resend verification code
     */
    public function resendVerificationCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if user already exists
        if (User::where('email', $data['email'])->exists()) {
            return response()->json([
                'message' => 'Email này đã được đăng ký. Vui lòng sử dụng email khác hoặc đăng nhập.',
            ], 422);
        }

        // Check if there's an active (unused, not expired) code
        $existingRecord = EmailVerificationCode::where('email', $data['email'])
            ->whereNull('verified_at')
            ->first();

        // If there's a recent code (less than 60 seconds ago), don't send yet
        if ($existingRecord && $existingRecord->created_at->diffInSeconds(now()) < 60) {
            return response()->json([
                'message' => 'Vui lòng chờ một chút trước khi yêu cầu mã mới.',
            ], 429);
        }

        // Invalidate old codes
        EmailVerificationCode::where('email', $data['email'])
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        // If there was previous registration data, use it
        $userData = $existingRecord && $existingRecord->user_data
            ? $existingRecord->user_data
            : null;

        EmailVerificationCode::create([
            'email' => $data['email'],
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(15),
            'user_data' => $userData,
        ]);

        Mail::to($data['email'])->queue(
            new EmailVerificationMail($data['email'], $code, $token)
        );

        return response()->json([
            'message' => 'Mã xác nhận mới đã được gửi đến email của bạn.',
        ]);
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
            'phone' => ['nullable', 'string', 'max:20'],
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

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email này không tồn tại trong hệ thống.'],
            ]);
        }

        // Invalidate old codes
        PasswordResetCode::where('email', $data['email'])
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        PasswordResetCode::create([
            'email' => $data['email'],
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($data['email'])->queue(
            new PasswordResetCodeMail($data['email'], $code, $token)
        );

        return response()->json([
            'message' => 'Mã xác nhận đã được gửi đến email của bạn.',
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $record = PasswordResetCode::where('email', $data['email'])
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác nhận không hợp lệ hoặc đã hết hạn.'],
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác nhận đã hết hạn. Vui lòng yêu cầu mã mới.'],
            ]);
        }

        return response()->json([
            'message' => 'Mã xác nhận hợp lệ.',
            'token' => $record->token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $record = PasswordResetCode::where('token', $data['token'])
            ->whereNull('used_at')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'token' => ['Liên kết đặt lại mật khẩu không hợp lệ.'],
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['Liên kết đặt lại mật khẩu đã hết hạn.'],
            ]);
        }

        $user = User::where('email', $record->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['Không tìm thấy tài khoản liên kết.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $record->update(['used_at' => now()]);

        // Invalidate all other codes for this email
        PasswordResetCode::where('email', $record->email)
            ->whereNull('used_at')
            ->where('id', '!=', $record->id)
            ->update(['used_at' => now()]);

        return response()->json([
            'message' => 'Mật khẩu đã được đặt lại thành công.',
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
            'phone' => $user->phone,
        ];
    }
}
