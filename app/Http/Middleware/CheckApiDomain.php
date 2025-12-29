<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiDomain
{
    /**
     * Allowed domains for API access
     */
    private $allowedDomains = [
        'api.zarniagoldgallery.ir',
        'api.zarniagoldgallery.com',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the origin or host from the request
        $origin = $request->header('Origin');
        $host = $request->header('Host');
        $referer = $request->header('Referer');
        
        // Extract domain from origin (remove protocol if present)
        $requestDomain = null;
        if ($origin) {
            $parsedUrl = parse_url($origin);
            $requestDomain = $parsedUrl['host'] ?? null;
        } elseif ($referer) {
            $parsedUrl = parse_url($referer);
            $requestDomain = $parsedUrl['host'] ?? null;
        } elseif ($host) {
            // For direct API calls, check the Host header
            // Remove port if present
            $requestDomain = explode(':', $host)[0];
        }
        
        // If no domain found, deny access
        if (!$requestDomain) {
            return response()->json([
                'message' => 'Access denied: Invalid origin'
            ], 403);
        }
        
        // Check if the domain is in the allowed list (exact match only)
        if (!in_array($requestDomain, $this->allowedDomains)) {
            return response()->json([
                'message' => 'Access denied: Domain not allowed'
            ], 403);
        }
        
        return $next($request);
    }
}

