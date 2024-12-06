<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        // Add debug logging
        Log::info('CheckAdminRole middleware', [
            'user' => $request->user(),
            'role' => $request->user() ? $request->user()->role : null
        ]);

        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
                'user_role' => $request->user() ? $request->user()->role : 'not authenticated'
            ], 403);
        }

        return $next($request);
    }
} 