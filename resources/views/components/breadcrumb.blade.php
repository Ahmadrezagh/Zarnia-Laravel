<div class="page-header">
    <div>
        <h2 class="main-content-name tx-24 mg-b-5">{{ $title }}</h2>
        <ol class="breadcrumb">
            @foreach ($items as $item)
                @if ($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    </div>
    <div class="d-flex">
        {{ $slot }}
    </div>
</div>
