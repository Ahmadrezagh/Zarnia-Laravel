<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Visit extends Model
{
    protected $fillable = [
        'ip','title', 'user_agent', 'url', 'referrer', 'user_id'
    ];

    // Scope for daily visits
    public function scopeDaily($query, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        return $query->whereDate('created_at', $date);
    }

    // Static method for total visits
    public static function getTotalVisits($startDate = null, $endDate = null)
    {
        $query = static::query();
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->count();
    }

    // Static method for unique visitors (based on IP)
    public static function getUniqueVisitors($startDate = null, $endDate = null)
    {
        $query = static::select('ip')->distinct();
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->count();
    }

    // Static method for top pages
    public static function getTopPages($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::select('url', 'title', DB::raw('count(*) as visits'))
            ->groupBy('url', 'title')
            ->orderBy('visits', 'desc');
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->limit($limit)->get();
    }

    // Static method for daily visits
    public static function getDailyVisits($days = 30)
    {
        return static::selectRaw('DATE(created_at) as date, COUNT(*) as visits')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // Static method for authenticated visits (logged-in users)
    public static function getAuthenticatedVisits($startDate = null, $endDate = null)
    {
        $query = static::whereNotNull('user_id');
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->count();
    }

    // Static method for anonymous visits (non-logged-in users)
    public static function getAnonymousVisits($startDate = null, $endDate = null)
    {
        $query = static::whereNull('user_id');
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->count();
    }

    // Static method for top referrers
    public static function getTopReferrers($limit = 10, $startDate = null, $endDate = null)
    {
        $query = static::select('referrer', DB::raw('count(*) as visits'))
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderBy('visits', 'desc');
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->limit($limit)->get();
    }

    // Static method for visits by user (specific user_id)
    public static function getVisitsByUser($userId, $startDate = null, $endDate = null)
    {
        $query = static::where('user_id', $userId);
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query->count();
    }

    // Static method for real-time visits (e.g., last 5 minutes)
    public static function getRealtimeVisits($minutes = 5)
    {
        return static::where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();
    }

}
