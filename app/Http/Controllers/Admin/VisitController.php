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
        ];

        return view('admin.visit.index', $data);
    }
}
