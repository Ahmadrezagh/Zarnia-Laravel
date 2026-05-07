<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use DeviceDetector\DeviceDetector;
use GeoIp2\Database\Reader;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'country_code', 'title', 'user_agent', 'url', 'referrer', 'user_id', 'reference'
    ];

    public function scopeDaily($query, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        return $query->whereDate('created_at', $date);
    }

    // Previous methods (getTotalVisits, getUniqueVisitors, etc.) remain as before

    // Summary of traffic for different periods
    public static function getTrafficSummary()
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $lastWeek = now()->subWeek()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'today' => static::daily($today)->count(),
            'yesterday' => static::daily($yesterday)->count(),
            'this_week' => static::where('created_at', '>=', $thisWeek)->count(),
            'last_week' => static::whereBetween('created_at', [$lastWeek, $thisWeek])->count(),
            'this_month' => static::where('created_at', '>=', $thisMonth)->count(),
            'last_month' => static::whereBetween('created_at', [$lastMonth, $thisMonth])->count(),
            'last_7_days' => static::where('created_at', '>=', now()->subDays(7))->count(),
            'last_30_days' => static::where('created_at', '>=', now()->subDays(30))->count(),
            'last_90_days' => static::where('created_at', '>=', now()->subDays(90))->count(),
            'last_6_months' => static::where('created_at', '>=', now()->subMonths(6))->count(),
            'all_time' => static::count(),
        ];
    }

    // Browser usage (count and percentage)
    public static function getBrowserUsage($startDate = null, $endDate = null)
    {
        $cacheKey = 'visit_browser_usage_' . md5($startDate.'|'.$endDate);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($startDate, $endDate) {
            $query = static::select('user_agent', DB::raw('count(*) as visits'))
                ->whereNotNull('user_agent')
                ->groupBy('user_agent')
                ->orderByDesc('visits');
            if ($startDate) $query->whereDate('created_at', '>=', $startDate);
            if ($endDate) $query->whereDate('created_at', '<=', $endDate);

            $userAgents = $query->limit(500)->get();

            $browsers = [];
            $total = 0;

            foreach ($userAgents as $row) {
                $dd = new DeviceDetector($row->user_agent);
                $dd->parse();
                $browser = $dd->getClient('name') ?? 'Unknown';
                $browsers[$browser] = ($browsers[$browser] ?? 0) + $row->visits;
                $total += $row->visits;
            }

            return collect($browsers)->map(function ($count, $browser) use ($total) {
                return [
                    'browser' => $browser,
                    'count' => $count,
                    'percentage' => $total ? round(($count / $total) * 100, 2) : 0,
                ];
            })->sortByDesc('count')->values();
        });
    }

    // Operating system usage
    public static function getOsUsage($startDate = null, $endDate = null)
    {
        $cacheKey = 'visit_os_usage_' . md5($startDate.'|'.$endDate);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($startDate, $endDate) {
            $query = static::select('user_agent', DB::raw('count(*) as visits'))
                ->whereNotNull('user_agent')
                ->groupBy('user_agent')
                ->orderByDesc('visits');
            if ($startDate) $query->whereDate('created_at', '>=', $startDate);
            if ($endDate) $query->whereDate('created_at', '<=', $endDate);

            $userAgents = $query->limit(500)->get();

            $os = [];
            $total = 0;

            foreach ($userAgents as $row) {
                $dd = new DeviceDetector($row->user_agent);
                $dd->parse();
                $osName = $dd->getOs('name') ?? 'Unknown';
                $os[$osName] = ($os[$osName] ?? 0) + $row->visits;
                $total += $row->visits;
            }

            return collect($os)->map(function ($count, $osName) use ($total) {
                return [
                    'os' => $osName,
                    'count' => $count,
                    'percentage' => $total ? round(($count / $total) * 100, 2) : 0,
                ];
            })->sortByDesc('count')->values();
        });
    }

    // Device type usage (desktop, mobile, tablet)
    public static function getDeviceUsage($startDate = null, $endDate = null)
    {
        $cacheKey = 'visit_device_usage_' . md5($startDate.'|'.$endDate);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($startDate, $endDate) {
            $query = static::select('user_agent', DB::raw('count(*) as visits'))
                ->whereNotNull('user_agent')
                ->groupBy('user_agent')
                ->orderByDesc('visits');
            if ($startDate) $query->whereDate('created_at', '>=', $startDate);
            if ($endDate) $query->whereDate('created_at', '<=', $endDate);

            $userAgents = $query->limit(500)->get();

            $devices = [];
            $total = 0;

            foreach ($userAgents as $row) {
                $dd = new DeviceDetector($row->user_agent);
                $dd->parse();
                $device = 'Unknown';
                if ($dd->isDesktop()) {
                    $device = 'Desktop';
                } elseif ($dd->isTablet()) {
                    $device = 'Tablet';
                } elseif ($dd->isMobile()) {
                    $device = 'Mobile';
                }

                $devices[$device] = ($devices[$device] ?? 0) + $row->visits;
                $total += $row->visits;
            }

            return collect($devices)->map(function ($count, $device) use ($total) {
                return [
                    'device' => $device,
                    'count' => $count,
                    'percentage' => $total ? round(($count / $total) * 100, 2) : 0,
                ];
            })->sortByDesc('count')->values();
        });
    }

    // Top countries
    public static function getTopCountries($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::select('country_code', DB::raw('count(*) as visits'))
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderBy('visits', 'desc');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->limit($limit)->get();
    }

    // Active authenticated users
    public static function getActiveUsers($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::select('user_id', DB::raw('count(*) as visits'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('visits', 'desc');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->limit($limit)->with('user')->get();
    }

    // Recent visitors
    public static function getRecentVisitors($limit = 10)
    {
        return static::select('ip', 'country_code', 'url', 'title', 'user_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    // Search engine referrals
    public static function getSearchEngineReferrals($startDate = null, $endDate = null)
    {
        $searchEngines = ['google.com', 'bing.com', 'yahoo.com', 'duckduckgo.com'];
        $query = static::select('referrer', DB::raw('count(*) as visits'))
            ->whereNotNull('referrer')
            ->where(function ($q) use ($searchEngines) {
                foreach ($searchEngines as $engine) {
                    $q->orWhere('referrer', 'like', "%$engine%");
                }
            })
            ->groupBy('referrer')
            ->orderBy('visits', 'desc');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->get();
    }

    // Online users (last 5 minutes)
    public static function getOnlineUsers($minutes = 5)
    {
        return static::select('ip')
            ->distinct()
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();
    }

    // Global distribution for map
    public static function getGlobalDistribution($startDate = null, $endDate = null)
    {
        $query = static::select('country_code', DB::raw('count(*) as visits'))
            ->whereNotNull('country_code')
            ->groupBy('country_code');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->get();
    }

    // Relationship for user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getTopPages($limit = 10, $startDate = null, $endDate = null)
    {
        $query = Visit::select('url', 'title', DB::raw('count(*) as visits'))
            ->groupBy('url', 'title') // group by both url and title
            ->orderBy('visits', 'desc');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->limit($limit)->get();
    }

    // Top referrers
    public static function getTopReferrers($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::select('referrer', DB::raw('count(*) as visits'))
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderBy('visits', 'desc');
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);
        return $query->limit($limit)->get();
    }

    // Daily visits
    public static function getDailyVisits($days = 30)
    {
        return static::selectRaw('DATE(created_at) as date, COUNT(*) as visits')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

}