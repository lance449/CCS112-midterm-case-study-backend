<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function createAdmin(Request $request)
    {
        Log::info('Starting createAdmin request');

        try {
            // Basic validation
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed'
            ]);

            Log::info('Validation passed');

            // Create user using the model
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin'
            ]);

            Log::info('User created', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Admin created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create admin user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                // Add any other user fields you want to return
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching user profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'currentPassword' => 'required_with:newPassword',
                'newPassword' => 'nullable|min:8|confirmed',
            ]);

            // Check current password if trying to change password
            if ($request->has('newPassword') && $request->filled('newPassword')) {
                if (!Hash::check($request->currentPassword, $user->password)) {
                    return response()->json([
                        'message' => 'Current password is incorrect'
                    ], 422);
                }
                $user->password = Hash::make($request->newPassword);
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 