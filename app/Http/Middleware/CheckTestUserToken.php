<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTestUserToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if bearer token matches the static test token
        $staticToken = 'aa20984c-eaff-485a-9548-2b734abb43b8';
        $bearerToken = $request->bearerToken();
        
        $isTestUser = false;
        
        if ($bearerToken) {
            // Check if token matches static token (handle both formats: plain token or {id}|{token})
            $tokenMatches = false;
            
            // Check if it's plain token format
            if ($bearerToken === $staticToken) {
                $tokenMatches = true;
            } else {
                // Sanctum token format is: {id}|{plainTextToken}
                // Split token and check if second part matches static token
                $tokenParts = explode('|', $bearerToken);
                if (count($tokenParts) === 2 && $tokenParts[1] === $staticToken) {
                    $tokenMatches = true;
                }
            }
            
            if ($tokenMatches) {
                // Handle test token authentication
                $testUser = User::where('phone', '09920435523')->first();
                
                if ($testUser) {
                    // Find the token record in database
                    $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_type', User::class)
                        ->where('tokenable_id', $testUser->id)
                        ->where('name', 'snapp_test_token')
                        ->first();
                    
                    if ($tokenRecord) {
                        // Attach token to user instance
                        $testUser->withAccessToken($tokenRecord);
                    }
                    
                    // Authenticate the test user
                    // Set on sanctum guard
                    Auth::guard('sanctum')->setUser($testUser);
                    
                    // Also set on default guard so auth()->user() works
                    Auth::setUser($testUser);
                    
                    // Set user resolver on request so $request->user() works
                    $request->setUserResolver(function () use ($testUser) {
                        return $testUser;
                    });
                    
                    $isTestUser = true;
                }
            }
        }
        
        // If token doesn't match test token, use Sanctum's authentication
        // Try to get user via Sanctum guard (this will authenticate if bearer token is valid)
        if (!$isTestUser) {
            // For non-test tokens, let Sanctum handle authentication
            // Access user() which will trigger Sanctum's authentication
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                // Sanctum authentication failed
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        } else {
            // For test token, user is already authenticated above
            // Just verify it's set
            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        }
        
        // Set as request attribute so controllers can access it
        $request->attributes->set('is_test_user', $isTestUser);
        
        return $next($request);
    }
}

