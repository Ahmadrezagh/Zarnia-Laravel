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
        
        // Get client IP address (handles Cloudflare and other proxies)
        $clientIp = $this->getRealClientIp($request);
        
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

    /**
     * Get the real client IP address, handling Cloudflare and other proxies.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getRealClientIp(Request $request): string
    {
        // Check Cloudflare header first (most reliable when behind Cloudflare)
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Fallback to X-Forwarded-For header (standard proxy header)
        // Get the first IP in the chain (real client IP)
        $xForwardedFor = $request->header('X-Forwarded-For');
        if ($xForwardedFor) {
            $ips = explode(',', $xForwardedFor);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Fallback to X-Real-IP header
        if ($request->header('X-Real-IP')) {
            $ip = $request->header('X-Real-IP');
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Last resort: use Laravel's ip() method
        return $request->ip();
    }
}

