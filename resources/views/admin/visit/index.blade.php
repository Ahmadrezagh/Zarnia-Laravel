@extends('layouts.panel')
@section('head')
    <style>
        .progress-bar{
            height: 100%;
        }
    </style>
@endsection
@section('content')
    <!-- Page Header -->
    <x-breadcrumb :title="'آمار'" :items="[
            ['label' => 'خانه', 'url' => route('home')],
            ['label' => 'آمار']
      ]"/>

    <!-- Row -->
    <x-page>
        <!-- Summary Traffic -->
        <div class="card">
            <div class="card-header">
                <h4>خلاصه ترافیک</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>امروز:</strong> {{ $traffic_summary['today'] }}</div>
                    <div class="col-md-3"><strong>دیروز:</strong> {{ $traffic_summary['yesterday'] }}</div>
                    <div class="col-md-3"><strong>این هفته:</strong> {{ $traffic_summary['this_week'] }}</div>
                    <div class="col-md-3"><strong>هفته گذشته:</strong> {{ $traffic_summary['last_week'] }}</div>
                    <div class="col-md-3"><strong>این ماه:</strong> {{ $traffic_summary['this_month'] }}</div>
                    <div class="col-md-3"><strong>ماه گذشته:</strong> {{ $traffic_summary['last_month'] }}</div>
                    <div class="col-md-3"><strong>۷ روز اخیر:</strong> {{ $traffic_summary['last_7_days'] }}</div>
                    <div class="col-md-3"><strong>۳۰ روز اخیر:</strong> {{ $traffic_summary['last_30_days'] }}</div>
                    <div class="col-md-3"><strong>۹۰ روز اخیر:</strong> {{ $traffic_summary['last_90_days'] }}</div>
                    <div class="col-md-3"><strong>۶ ماه گذشته:</strong> {{ $traffic_summary['last_6_months'] }}</div>
                    <div class="col-md-3"><strong>کل:</strong> {{ $traffic_summary['all_time'] }}</div>
                </div>
            </div>
        </div>

        <!-- Browser Usage -->
        <div class="card">
            <div class="card-header">
                <h4>آمار استفاده از مرورگر</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>مرورگر</th>
                        <th>تعداد</th>
                        <th>درصد</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($browser_usage as $item)
                        @php
                            $browserIcon = match (strtolower($item['browser'])) {
                                'chrome', 'google chrome' => 'chrome',
                                'firefox' => 'firefox',
                                'safari','mobile safari' => 'apple',
                                'edge', 'microsoft edge' => 'edge',
                                'opera' => 'opera',
                                'internet explorer' => 'internet-explorer',
                                default => 'globe'
                            };
                        @endphp
                        <tr>
                            <td>
                                <i class="fa-brands fa-{{ $browserIcon }} me-2"></i>
                                {{ $item['browser'] }}
                            </td>
                            <td>{{ $item['count'] }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary bg-opacity-25" role="progressbar"
                                         style="width: {{ $item['percentage'] }}%;"
                                         aria-valuenow="{{ $item['percentage'] }}" aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $item['percentage'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- OS Usage -->
        <div class="card">
            <div class="card-header">
                <h4>سیستم‌عامل‌های پر کاربرد</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>سیستم‌عامل</th>
                        <th>تعداد</th>
                        <th>درصد</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($os_usage as $item)
                        @php
                            $osIcon = match (strtolower($item['os'])) {
                                'windows' => 'windows',
                                'mac','macos', 'apple','mac os x','ios' => 'apple',
                                'android' => 'android',
                                'linux' => 'linux',
                                default => 'desktop'
                            };
                        @endphp
                        <tr>
                            <td>
                                <i class="fa-brands fa-{{ $osIcon }} me-2"></i>
                                {{ $item['os'] }}
                            </td>
                            <td>{{ $item['count'] }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary bg-opacity-25" role="progressbar"
                                         style="width: {{ $item['percentage'] }}%;"
                                         aria-valuenow="{{ $item['percentage'] }}" aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $item['percentage'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Device Usage -->
        <div class="card">
            <div class="card-header">
                <h4>تفکیک استفاده از دستگاه</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>دستگاه</th>
                        <th>تعداد</th>
                        <th>درصد</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($device_usage as $item)
                        <tr>
                            <td>
                                <i class="fas fa-{{ strtolower($item['device']) === 'desktop' ? 'desktop' : (strtolower($item['device']) === 'mobile' ? 'mobile-alt' : 'tablet-alt') }}"></i>
                                {{ $item['device'] }}
                            </td>
                            <td>{{ $item['count'] }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary bg-opacity-25" role="progressbar"
                                         style="width: {{ $item['percentage'] }}%;"
                                         aria-valuenow="{{ $item['percentage'] }}" aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $item['percentage'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Countries -->
        <div class="card">
            <div class="card-header">
                <h4>کشورهای برتر</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>کشور</th>
                        <th>بازدید</th>
                        <th>درصد</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $total_visits = $top_countries->sum('visits');
                    @endphp
                    @foreach ($top_countries as $item)
                        @php
                            $percentage = $total_visits ? round(($item->visits / $total_visits) * 100, 2) : 0;
                        @endphp
                        <tr>
                            <td>
                                <img src="https://flagcdn.com/16x12/{{ strtolower($item->country_code) }}.png"
                                     alt="{{ $item->country_code }}" style="margin-left: 5px;">
                                {{ $item->country_code }}
                            </td>
                            <td>{{ $item->visits }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary bg-opacity-25" role="progressbar"
                                         style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other sections (Top Pages, Active Users, Recent Visitors, etc.) remain unchanged -->
        <div class="card">
            <div class="card-header">
                <h4>برترین برگه‌ها</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>آدرس</th>
                        <th>بازدید</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($top_pages as $item)
                        <tr>
                            <td>{{ $item->title ?? 'بدون عنوان' }}</td>
                            <td><a href="{{ $item->url }}">{{ $item->url }}</a></td>
                            <td>{{ $item->visits }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>فعال‌ترین بازدیدکنندگان</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>بازدید</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($active_users as $item)
                        <tr>
                            <td>{{ $item->user ? $item->user->name : 'Unknown' }}</td>
                            <td>{{ $item->visits }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>بازدیدکنندگان اخیر</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>IP</th>
                        <th>کشور</th>
                        <th>کاربر</th>
                        <th>صفحه</th>
                        <th>زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($recent_visitors as $item)
                        <tr>
                            <td>{{ $item->ip }}</td>
                            <td>{{ $item->country_code ?? 'Unknown' }}</td>
                            <td>{{ $item->user ? $item->user->name : 'Guest' }}</td>
                            <td><a href="{{ $item->url }}">{{ $item->title ?? 'بدون عنوان' }}</a></td>
                            <td>{{ \Carbon\Carbon::parse($item->created_at)->locale('fa')->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>برترین ارجاع‌دهندگان</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>ارجاع‌دهنده</th>
                        <th>بازدید</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($top_referrers as $item)
                        <tr>
                            <td><a href="{{ $item->referrer }}">{{ $item->referrer }}</a></td>
                            <td>{{ $item->visits }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>در حال حاضر آنلاین</h4>
            </div>
            <div class="card-body">
                <p>کاربران آنلاین: {{ $online_users }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>روند ترافیک</h4>
                <select id="traffic-trend-type">
                    <option value="daily">روزانه</option>
                    <option value="weekly">هفتگی</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="traffic-trend-chart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>ارجاعات از موتورهای جستجو</h4>
            </div>
            <div class="card-body">
                <canvas id="search-engine-chart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>توزیع جهانی بازدیدکنندگان</h4>
            </div>
            <div class="card-body">
                <div id="world-map" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </x-page>



    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/jquery-jvectormap.min.css"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.5/maps/jquery-jvectormap-world-mill.js"></script>
        <script>
            // Ensure jVectorMap and jQuery are loaded before running map code
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof jQuery === 'undefined') {
                    console.error('jQuery is not loaded. Please check the jQuery script source.');
                    return;
                }
                if (typeof jvm === 'undefined') {
                    console.error('jVectorMap is not loaded. Please check the jVectorMap script source.');
                    return;
                }

                // Traffic Trend Chart (unchanged)
                const dailyVisits = @json($daily_visits);
                const trafficTrendChart = new Chart(document.getElementById('traffic-trend-chart'), {
                    type: 'line',
                    data: {
                        labels: dailyVisits.map(item => item.date),
                        datasets: [{
                            label: 'بازدیدها',
                            data: dailyVisits.map(item => item.visits),
                            borderColor: '#36a2eb',
                            fill: false
                        }]
                    },
                    options: {responsive: true}
                });

                document.getElementById('traffic-trend-type').addEventListener('change', (e) => {
                    const type = e.target.value;
                    if (type === 'weekly') {
                        const weeklyData = [];
                        let weekVisits = 0;
                        let weekStart = null;
                        dailyVisits.forEach((item, index) => {
                            const date = new Date(item.date);
                            if (!weekStart) weekStart = date;
                            weekVisits += item.visits;
                            if (date.getDay() === 0 || index === dailyVisits.length - 1) {
                                weeklyData.push({date: weekStart.toISOString().split('T')[0], visits: weekVisits});
                                weekStart = null;
                                weekVisits = 0;
                            }
                        });
                        trafficTrendChart.data.labels = weeklyData.map(item => item.date);
                        trafficTrendChart.data.datasets[0].data = weeklyData.map(item => item.visits);
                        trafficTrendChart.update();
                    } else {
                        trafficTrendChart.data.labels = dailyVisits.map(item => item.date);
                        trafficTrendChart.data.datasets[0].data = dailyVisits.map(item => item.visits);
                        trafficTrendChart.update();
                    }
                });

                // Search Engine Referrals Chart (unchanged)
                const searchEngineReferrals = @json($search_engine_referrals);
                new Chart(document.getElementById('search-engine-chart'), {
                    type: 'pie',
                    data: {
                        labels: searchEngineReferrals.map(item => item.referrer),
                        datasets: [{
                            data: searchEngineReferrals.map(item => item.visits),
                            backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0'],
                        }]
                    },
                    options: {responsive: true}
                });

                // World Map with jVectorMap
                const globalDistribution = @json($global_distribution);

                // Create a data object for visits by country code
                const visitsByCountry = {};
                globalDistribution.forEach(item => {
                    visitsByCountry[item.country_code] = item.visits;
                });

                // Initialize jVectorMap
                $('#world-map').vectorMap({
                    map: 'world_mill',
                    backgroundColor: '#f8f9fa',
                    regionStyle: {
                        initial: {
                            fill: '#d3d3d3',
                            "fill-opacity": 1,
                            stroke: '#ffffff',
                            "stroke-width": 0.5,
                            "stroke-opacity": 1
                        },
                        hover: {
                            "fill-opacity": 0.8,
                            cursor: 'pointer'
                        }
                    },
                    series: {
                        regions: [{
                            values: visitsByCountry,
                            scale: ['#C8EEFF', '#0071A4'],
                            normalizeFunction: 'polynomial'
                        }]
                    },
                    onRegionTipShow: function(e, el, code) {
                        const visits = visitsByCountry[code] || 0;
                        const countryName = el.html(); // Default country name from jVectorMap
                        const flagUrl = `https://flagcdn.com/16x12/${code.toLowerCase()}.png`;
                        el.html(`
                        <div style="text-align: center;">
                            <img src="${flagUrl}" alt="Flag" style="width: 16px; height: 12px; margin-right: 5px;">
                            <strong>${countryName}</strong><br>
                            بازدید: ${visits}
                        </div>
                    `);
                    }
                });
            });
        </script>
    @endpush
@endsection