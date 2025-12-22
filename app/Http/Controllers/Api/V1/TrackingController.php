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
            'reference' => 'nullable|string|max:255',
        ]);

        $ip = $validated['ip'];
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipParts = explode('.', $ip);
            $ip = implode('.', array_slice($ipParts, 0, 3)) . '.0';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = substr_replace($ip, ':0000:0000:0000:0000', -19);
        }

        // Check for bots in user agent (check both validated user_agent and request userAgent)
        $userAgent = $validated['user_agent'] ?? $request->userAgent();
        if ($userAgent && $this->isBot($userAgent)) {
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
            'referrer' => (isset($validated['referrer']) && $validated['referrer']) ? $validated['referrer'] : null,
            'user_id' => $userId,
            'reference' => $validated['reference'] ?? null,
        ]);

        return response()->json(['status' => 'tracked'], 200);
    }

    /**
     * Check if user agent is a bot
     */
    private function isBot(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $botPatterns = [
            'bot', 'crawl', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'perl', 'ruby', 'php', 'http',
            'feed', 'rss', 'parser', 'monitor', 'check', 'ping',
            'validator', 'indexer', 'fetcher', 'extractor', 'analyzer',
            'collector', 'harvester', 'downloader', 'tool', 'api',
            'client', 'library', 'framework', 'engine', 'agent',
            'service', 'daemon', 'automation', 'headless', 'phantom',
            'selenium', 'webdriver', 'chromium', 'gecko', 'webkit'
        ];

        $userAgentLower = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
