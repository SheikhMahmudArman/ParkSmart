<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $d = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:8|confirmed']);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => Hash::make($d['password']), 'role' => 'driver']);

        return response()->json(['message' => 'Account created. Please sign in.', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $d = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $d['email'])->first();
        if (!$user || !Hash::check($d['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        return ['user' => $user, 'token' => $user->createToken('parksmart', ['*'], now()->addDays(7))->plainTextToken];
    }

    public function user(Request $request)
    {
        return ['user' => $request->user()];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ['message' => 'Signed out'];
    }
}
