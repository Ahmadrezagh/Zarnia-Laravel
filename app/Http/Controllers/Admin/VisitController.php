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
        $deletedCount = $this->getSearchEngineBotQuery()->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} بازدید رباتی حذف شد",
            'deleted_count' => $deletedCount
        ]);
    }

    private function getBotVisitsCount(): int
    {
        return $this->getSearchEngineBotQuery()->count();
    }

    /**
     * Get query for search engine bots only
     */
    private function getSearchEngineBotQuery()
    {
        return Visit::where(function ($query) {
            // Only detect search engine bots
            $searchEngineBots = [
                'googlebot',
                'bingbot',
                'slurp', // Yahoo
                'duckduckbot',
                'baiduspider',
                'yandexbot',
                'sogou',
                'exabot',
                'facebot', // Facebook
                'ia_archiver', // Alexa
                'ahrefsbot',
                'semrushbot',
                'mj12bot',
                'dotbot',
                'msnbot',
                'teoma', // Ask.com
                'gigabot',
                'scoutjet',
            ];

            foreach ($searchEngineBots as $index => $bot) {
                if ($index === 0) {
                    $query->whereRaw('LOWER(user_agent) LIKE ?', ['%' . $bot . '%']);
                } else {
                    $query->orWhereRaw('LOWER(user_agent) LIKE ?', ['%' . $bot . '%']);
                }
            }
        });
    }
}
