<div class="table-responsive" style="min-height: 500px">
    <table class="table table-bordered" id="{{ $id }}" data-url="{{ $url }}">
        <thead>
        <tr>
            @foreach($columns as $column)
                <th>{{ $column['label'] }}</th>
            @endforeach
            @if($actions)
                <th>عملیات</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                @foreach($columns as $column)
                    <td>
                        @if($column['type'] === 'image')
                            <img src="{{ $item[$column['key']] }}" style="width: 100px; height: 100px; border-radius: 50%">
                        @elseif($column['type'] === 'copiableText')
                            <x-form.copiable-component :content="$item[$column['key']]" />

                        @elseif($column['type'] === 'binaryCondition')
                            @if($item[$column['key']] == '1')
                                <button class="btn btn-success">{{$column['texts']['true']}}</button>
                            @else
                                <button class="btn btn-danger">{{$column['texts']['false']}}</button>
                            @endif
                        @else
                            {{ $item[$column['key']] }}
                        @endif
                    </td>
                @endforeach

                @if($actions)
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                                عملیات
                            </button>
                            <div class="dropdown-menu">
                                @foreach($actions as $action)
                                    @php
                                        $url = null;

                                        // Handle Laravel named routes
                                        if (isset($action['route'])) {
                                            $routeName = $action['route'][0];
                                            $routeParams = $action['route'][1];
                                            foreach ($routeParams as $key => $value) {
                                                if (str_contains($value, '{') && str_contains($value, '}')) {
                                                    $paramKey = trim($value, '{}');
                                                    $routeParams[$key] = $item[$paramKey] ?? '';
                                                }
                                            }
                                            $url = route($routeName, $routeParams);
                                        }

                                        // Handle plain URLs with optional placeholders
                                        elseif (isset($action['url'])) {
                                            $url = $action['url'];
                                            foreach ($item->getAttributes() as $key => $value) {
                                                $url = str_replace("{" . $key . "}", $value, $url);
                                            }
                                        }
                                    @endphp

                                    @if($url)
                                        <a href="{{ $url }}" class="dropdown-item" target="_blank">
                                            {{ $action['label'] }}
                                        </a>
                                    @else
                                        <button class="dropdown-item" data-toggle="modal" data-target="#{{ $action['type'] }}-{{ $item->id }}">
                                            {{ $action['label'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if(method_exists($items, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links() }}
    </div>
@endif

{!! $slot !!}
