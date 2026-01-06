<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/auth/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','unique:users,email'],
            'password' => ['required','string','min:6'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Passport: tạo personal access token
        $token = $user->createToken('api-token')->accessToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không đúng.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->accessToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    // GET /api/auth/user (yêu cầu Bearer Token)
    public function me(Request $request)
    {
        // Trả trực tiếp object user để khớp phía Flutter (AuthService.me())
        return response()->json($request->user());
    }

    // PUT /api/auth/user (yêu cầu Bearer Token) — cập nhật tên
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->name = $data['name'];
        $user->save();

        // Trả trực tiếp object user (không bọc trong { user: ... })
        return response()->json($user, 200);
    }

    // POST /api/auth/change-password (yêu cầu Bearer Token)
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'old_password' => ['required','string'],
            'new_password' => ['required','string','min:6'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!Hash::check($data['old_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng',
            ], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['success' => true], 200);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        // Passport: thu hồi token hiện tại
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }
}
