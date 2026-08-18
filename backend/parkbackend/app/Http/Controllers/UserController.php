<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
}