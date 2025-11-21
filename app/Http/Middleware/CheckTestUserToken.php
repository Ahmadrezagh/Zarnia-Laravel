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
                // Find and authenticate the test user
                $testUser = User::where('phone', '09920435523')->first();
                
                if ($testUser) {
                    // Find the token record in database
                    $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_type', User::class)
                        ->where('tokenable_id', $testUser->id)
                        ->where('name', 'snapp_test_token')
                        ->first();
                    
                    if ($tokenRecord) {
                        // Set the token on the request so Sanctum can recognize it
                        // Format: {id}|{token}
                        $fullToken = $tokenRecord->id . '|' . $staticToken;
                        $request->headers->set('Authorization', 'Bearer ' . $fullToken);
                    } else {
                        // If token record not found, just authenticate the user
                        // Remove Authorization header so Sanctum doesn't validate it
                        $request->headers->remove('Authorization');
                        Auth::guard('sanctum')->setUser($testUser);
                        $request->setUserResolver(function () use ($testUser) {
                            return $testUser;
                        });
                    }
                    
                    $isTestUser = true;
                }
            }
        }
        
        // Set as request attribute so controllers can access it
        $request->attributes->set('is_test_user', $isTestUser);
        
        return $next($request);
    }
}
