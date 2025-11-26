<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPanelIpAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the allowed IPs setting
        $allowedIps = Setting::getValue('panel_allowed_ips');
        
        // If setting is #, allow all IPs
        if ($allowedIps === '#') {
            return $next($request);
        }
        
        // If setting is empty or null, deny all access
        if (empty($allowedIps)) {
            abort(403, 'Access denied.');
        }
        
        // Get client IP address
        $clientIp = $request->ip();
        
        // Parse comma-separated IPs, trim whitespace, and filter empty values
        $allowedIpList = array_filter(
            array_map('trim', explode(',', $allowedIps))
        );
        
        // Check if client IP is in the allowed list
        if (!in_array($clientIp, $allowedIpList)) {
            abort(403, 'Access denied. Your IP address is not allowed.');
        }
        
        return $next($request);
    }
}

