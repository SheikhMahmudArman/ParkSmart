<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users - List all users (Admin only)
    public function index()
    {
        $users = DB::table('users')->get();
        return response()->json($users);
    }

    // DELETE /api/users/{id} - Delete a user (Admin only)
    public function destroy($id)
    {
        $deleted = DB::table('users')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['message' => 'User deleted successfully']);
    }

    // GET /api/users/{id} - Get specific user
    public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    // PUT /api/users/{id} - Update user
    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'vehicle' => 'nullable|string'
        ]);

        $validated['updated_at'] = now();

        DB::table('users')->where('id', $id)->update($validated);

        $updatedUser = DB::table('users')->where('id', $id)->first();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $updatedUser
        ]);
    }

    // GET /api/users/{id}/vehicles - Get user's vehicles
    public function userVehicles($userId)
    {
        $vehicles = DB::table('vehicles')->where('user_id', $userId)->get();
        return response()->json($vehicles);
    }

    // GET /api/users/{id}/notifications - Get user's notifications
    public function notifications($userId)
    {
        // If you have a Notification model, use it
        // For now, return empty array or sample data
        return response()->json([]);
    }

    // GET /api/staff - Get all staff members (Admin)
    public function staff()
    {
        $staff = DB::table('users')
            ->whereIn('role', ['staff', 'admin'])
            ->get();
        return response()->json($staff);
    }

    // POST /api/staff - Create staff member (Admin)
    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:staff,admin',
            'assigned_lot' => 'nullable|string'
        ]);

        $id = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'assigned_lot' => $validated['assigned_lot'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = DB::table('users')->where('id', $id)->first();

        return response()->json($user, 201);
    }

    // PUT /api/staff/{id} - Update staff member (Admin)
    public function updateStaff(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:staff,admin',
            'assigned_lot' => 'nullable|string'
        ]);

        $validated['updated_at'] = now();

        DB::table('users')->where('id', $id)->update($validated);

        $updatedUser = DB::table('users')->where('id', $id)->first();

        return response()->json($updatedUser);
    }

    // DELETE /api/staff/{id} - Delete staff member (Admin)
    public function destroyStaff($id)
    {
        $deleted = DB::table('users')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json(['message' => 'Staff member deleted successfully']);
    }
}