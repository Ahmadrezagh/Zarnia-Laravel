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
        <link rel="stylesheet" href="{{ asset('map/jqvmap.min.css') }}"/>
        <script src="{{ asset('map/jquery.vmap.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/jqvmap@1.5.1/dist/maps/jquery.vmap.world.js"></script>


        <script>
            function initWorldMap() {
                if (typeof jQuery.fn.vectorMap === 'undefined') {
                    console.error('❌ JQVMap core library not loaded.');
                    renderFallbackMap();
                    return false;
                }

                if (typeof jQuery.fn.vectorMap.maps === 'undefined') {
                    console.error('❌ No maps found in JQVMap.');
                    renderFallbackMap();
                    return false;
                }

                // Detect map name dynamically
                const availableMaps = Object.keys(jQuery.fn.vectorMap.maps);
                const mapName = availableMaps.includes('world_en') ? 'world_en' :
                    availableMaps.includes('world') ? 'world' : null;

                if (!mapName) {
                    console.error('❌ World map data not found in JQVMap.');
                    renderFallbackMap();
                    return false;
                }

                const globalDistribution = @json($global_distribution);
                const visitsByCountry = {};
                globalDistribution.forEach(item => {
                    visitsByCountry[item.country_code] = item.visits;
                });

                $('#world-map').vectorMap({
                    map: mapName,
                    backgroundColor: '#f8f9fa',
                    borderColor: '#ffffff',
                    borderWidth: 0.5,
                    color: '#d3d3d3',
                    hoverOpacity: 0.8,
                    enableZoom: false,
                    showTooltip: true,
                    normalizeFunction: 'polynomial',
                    scaleColors: ['#C8EEFF', '#0071A4'],
                    values: visitsByCountry,
                    onRegionLabelShow: function(e, el, code) {
                        const visits = visitsByCountry[code] || 0;
                        const countryName = el.html();
                        const flagUrl = `https://flagcdn.com/16x12/${code.toLowerCase()}.png`;
                        el.html(`
                        <div style="text-align:center;">
                            <img src="${flagUrl}" alt="Flag" style="width:16px;height:12px;margin-right:5px;">
                            <strong>${countryName}</strong><br>
                            بازدید: ${visits}
                        </div>
                    `);
                    }
                });

                console.log(`✅ JQVMap initialized successfully with map: ${mapName}`);
                return true;
            }

            function renderFallbackMap() {
                console.warn('⚠️ Using Chart.js fallback for world map.');
                const globalDistribution = @json($global_distribution);

                if (globalDistribution.length === 0) {
                    document.getElementById('world-map').innerHTML =
                        '<p style="text-align:center;padding:20px;">No global data available.</p>';
                    return;
                }

                const countryCodes = globalDistribution.map(item => item.country_code);
                const visits = globalDistribution.map(item => item.visits);
                const totalVisits = visits.reduce((a, b) => a + b, 0);

                const canvas = document.createElement('canvas');
                document.getElementById('world-map').innerHTML = '';
                document.getElementById('world-map').appendChild(canvas);

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: countryCodes,
                        datasets: [{
                            data: visits,
                            backgroundColor: [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                                '#9966FF', '#FF9F40', '#C9CBCF', '#FFCD56'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 20 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const code = countryCodes[context.dataIndex];
                                        const visits = context.parsed;
                                        const percentage = ((visits / totalVisits) * 100).toFixed(1);
                                        const flagUrl = `https://flagcdn.com/16x12/${code.toLowerCase()}.png`;
                                        return [
                                            `${code} (${percentage}%)`,
                                            `بازدید: ${visits}`
                                        ];
                                    }
                                }
                            }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (typeof jQuery === 'undefined') {
                    console.error('❌ jQuery is not loaded.');
                    renderFallbackMap();
                    return;
                }

                initWorldMap();
            });
        </script>
    @endpush


@endsection