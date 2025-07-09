
<div class="row row-sm">
    <div class="col-lg-12">
        <div class="card custom-card overflow-hidden">
                @isset($header)
                    <div class="card-header">
                        {{ $header }}
                    </div>
                @endisset
            <div class="card-body">
                {{ $slot }}

            </div>
        </div>
    </div>
</div>
