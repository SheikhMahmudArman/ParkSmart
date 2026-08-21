<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users - List all users (Admin only)
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // DELETE /api/users/{id} - Delete a user (Admin only)
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    // GET /api/users/{id} - Get specific user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // PUT /api/users/{id} - Update user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'vehicle' => 'nullable|string'
        ]);

        $user->update($validated);
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // GET /api/users/{id}/vehicles - Get user's vehicles
    public function userVehicles($userId)
    {
        $vehicles = Vehicle::where('user_id', $userId)->get();
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
        $staff = User::whereIn('role', ['staff', 'admin'])->get();
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'assigned_lot' => $validated['assigned_lot'] ?? null
        ]);

        return response()->json($user, 201);
    }

    // PUT /api/staff/{id} - Update staff member (Admin)
    public function updateStaff(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:staff,admin',
            'assigned_lot' => 'nullable|string'
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    // DELETE /api/staff/{id} - Delete staff member (Admin)
    public function destroyStaff($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Staff member deleted successfully']);
    }
}