<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    // public function getUser(Request $request)
    // {
    //     return response()->json(['user' => auth()->user()]);
    // }

    public function getAllUsers()
    {
        // $users = User::all();
        $users = User::with(['meta', 'profile'])->get();

        // Count Users Based on Status
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();
        $pendingUsers = $users->where('status', 'pending')->count();

        return response()->json([
            'users'             => $users,
            'total_users'       => $totalUsers,
            'active_users'      => $activeUsers,
            'inactive_users'    => $inactiveUsers,
            'pending_users'     => $pendingUsers
        ], 200);
    }

    public function createUser(Request $request)
    {
        // Validate Input
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'role' => 'required|in:admin,customer',
            'status' => 'required|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create User
        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        // Insert username into `user_meta` table
        $userMeta = UserMeta::create([
            'user_id' => $user->id,
            'username' => $request->username,
        ]);

        // Fetch user with meta data
        $userWithMeta = User::with('meta')->find($user->id);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $userWithMeta->id,
                'name' => $userWithMeta->name,
                'email' => $userWithMeta->email,
                'role' => $userWithMeta->role,
                'status' => $userWithMeta->status,
                'created_at' => $userWithMeta->created_at,
                'updated_at' => $userWithMeta->updated_at,
                'meta' => [
                    'username' => $userWithMeta->meta->username ?? null
                ]
            ]
        ], 201);
    }
}
