<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required'
        ]);

        if ($data) {

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        }

        $user->assignRole('user');

        return response()->json([
            'status' => true,
            'message' => 'User Added Successfully',
            'data' => $user->createToken($user->email)->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required'
        ]);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'credentials does not match',
            ]);
        }

        $email = $data['email'];
        $password = $data['password'];
        $user = User::where('email', $email)->first();

        if ($user) {
            if (Hash::check($password, $user->password)) {
                $token = $user->createToken($email)->plainTextToken;
                return response()->json([
                    'status' => true,
                    'user' => $user,
                    'token' => $token,
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                ], 404);
            }
        }
    }
}
