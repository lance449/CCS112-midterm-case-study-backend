<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function profile()
    {
        try {
            $user = auth()->user();
            return response()->json([
                'name' => $user->name,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'currentPassword' => 'required_with:newPassword',
                'newPassword' => 'nullable|min:8|confirmed',
            ]);

            if (isset($validated['currentPassword'])) {
                if (!Hash::check($validated['currentPassword'], $user->password)) {
                    return response()->json([
                        'message' => 'Current password is incorrect'
                    ], 422);
                }
                $user->password = Hash::make($validated['newPassword']);
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
            return response()->json([
                'message' => 'Error updating profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 