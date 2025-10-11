<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public function trackVisit(Request $request)
    {
        // Validate input from frontend
        $validated = $request->validate([
            'ip' => 'required|ip', // Validate as real IP
            'url' => 'required|url',
            'title' => 'nullable|string|max:255', // Limit title length
            'referrer' => 'nullable|url',
            'user_agent' => 'nullable|string|max:1000', // Limit user agent length
        ]);

        // Anonymize IP
        $ip = $validated['ip'];
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipParts = explode('.', $ip);
            $ip = implode('.', array_slice($ipParts, 0, 3)) . '.0';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = substr_replace($ip, ':0000:0000:0000:0000', -19); // Optional: anonymize IPv6
        }

        // Skip bots
        if ($request->userAgent() && preg_match('/bot|crawl|spider/i', $request->userAgent())) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Get user_id from Sanctum guard if authenticated
        $userId = Auth::guard('sanctum')->id(); // Returns null if not authenticated

        // Create visit record
        Visit::create([
            'ip' => $ip,
            'title' => $validated['title'],
            'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
            'url' => $validated['url'],
            'referrer' => $validated['referrer'],
            'user_id' => $userId, // Will be null for non-logged-in users
        ]);

        return response()->json(['status' => 'tracked'], 200);
    }
}
