<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GeoIp2\Database\Reader;
class TrackingController extends Controller
{
    public function trackVisit(Request $request)
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
            'url' => 'required|url',
            'title' => 'nullable|string|max:255',
            'referrer' => 'nullable|url',
            'user_agent' => 'nullable|string|max:1000',
        ]);

        $ip = $validated['ip'];
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipParts = explode('.', $ip);
            $ip = implode('.', array_slice($ipParts, 0, 3)) . '.0';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = substr_replace($ip, ':0000:0000:0000:0000', -19);
        }

        if ($request->userAgent() && preg_match('/bot|crawl|spider/i', $request->userAgent())) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Get country code from IP
        $countryCode = null;
        try {

            $dbPath = base_path('GeoLite2-Country.mmdb');

            $reader = new Reader($dbPath);
            $record = $reader->country($ip);
            $countryCode = $record->country->isoCode;
        } catch (\Exception $e) {
            // Fallback to null if GeoIP fails
        }

        $userId = Auth::guard('sanctum')->id();

        Visit::create([
            'ip' => $ip,
            'country_code' => $countryCode,
            'title' => $validated['title'],
            'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
            'url' => $validated['url'],
            'referrer' => $validated['referrer'],
            'user_id' => $userId,
        ]);

        return response()->json(['status' => 'tracked'], 200);
    }
}
