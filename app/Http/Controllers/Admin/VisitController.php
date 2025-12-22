<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index()
    {
        $data = [
            'traffic_summary' => Visit::getTrafficSummary(),
            'browser_usage' => Visit::getBrowserUsage(),
            'os_usage' => Visit::getOsUsage(),
            'device_usage' => Visit::getDeviceUsage(),
            'top_countries' => Visit::getTopCountries(10),
            'active_users' => Visit::getActiveUsers(10),
            'recent_visitors' => Visit::getRecentVisitors(10),
            'top_pages' => Visit::getTopPages(10),
            'top_referrers' => Visit::getTopReferrers(10),
            'search_engine_referrals' => Visit::getSearchEngineReferrals(),
            'online_users' => Visit::getOnlineUsers(),
            'global_distribution' => Visit::getGlobalDistribution(),
            'daily_visits' => Visit::getDailyVisits(30), // For traffic trend chart
            'bot_visits_count' => $this->getBotVisitsCount(),
        ];

        return view('admin.visit.index', $data);
    }

    public function clearBotVisits()
    {
        $deletedCount = Visit::where(function ($query) {
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

            foreach ($botPatterns as $index => $pattern) {
                if ($index === 0) {
                    $query->whereRaw('LOWER(user_agent) LIKE ?', ['%' . $pattern . '%']);
                } else {
                    $query->orWhereRaw('LOWER(user_agent) LIKE ?', ['%' . $pattern . '%']);
                }
            }
        })->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} بازدید رباتی حذف شد",
            'deleted_count' => $deletedCount
        ]);
    }

    private function getBotVisitsCount(): int
    {
        return Visit::where(function ($query) {
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

            foreach ($botPatterns as $index => $pattern) {
                if ($index === 0) {
                    $query->whereRaw('LOWER(user_agent) LIKE ?', ['%' . $pattern . '%']);
                } else {
                    $query->orWhereRaw('LOWER(user_agent) LIKE ?', ['%' . $pattern . '%']);
                }
            }
        })->count();
    }
}
