@props([
    'hasCheckbox' => false
])
<div class="table-responsive" style="min-height: 500px">
    <input type="hidden" id="selectedValues">
    <table class="table table-bordered" id="{{ $id }}" data-url="{{ $url }}">
        <thead>
        <tr>
            @if($hasCheckbox)
               <th>
                   <input type="checkbox" class="form-control" id="selectAll">
               </th> 
            @endif
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

                @if($hasCheckbox)
                    <td>
                        <input type="checkbox" class="form-control custom-select-option" value="{{$item['id']}}" />
                    </td>
                @endif
                @foreach($columns as $column)
                    <td>
                        @php
                            $url = null;

                            // Handle Laravel named routes
                            if (isset($column['route'])) {
                                $routeName = $column['route'][0];
                                $routeParams = $column['route'][1];
                                foreach ($routeParams as $key => $value) {
                                    if (str_contains($value, '{') && str_contains($value, '}')) {
                                        $paramKey = trim($value, '{}');
                                        $routeParams[$key] = $item[$paramKey] ?? '';
                                    }
                                }
                                $url = route($routeName, $routeParams);
                            }

                            // Handle plain URLs with optional placeholders
                            elseif (isset($column['url'])) {
                                $url = $column['url'];
                                foreach ($item->getAttributes() as $key => $value) {
                                    $url = str_replace("{" . $key . "}", $value, $url);
                                }
                            }
                        @endphp
                        @if($url)
                            <a href="{{$url}}" target="_blank">{{ $item[$column['key']] }}</a>
                        @elseif($column['type'] === 'image')
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

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize hidden input with empty array
            const hiddenInput = document.getElementById('selectedValues');
            if (hiddenInput && !hiddenInput.value) {
                hiddenInput.value = JSON.stringify([]);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('click', function () {
                    const isChecked = this.checked;
                    const checkboxes = document.querySelectorAll('.custom-select-option');

                    checkboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });

                    updateHiddenInput();
                });
            }
        });

        document.querySelectorAll('.custom-select-option').forEach(checkbox => {
            checkbox.addEventListener('click', function() {
                const allCheckboxes = document.querySelectorAll('.custom-select-option');
                const selectAll = document.getElementById('selectAll');

                // If any checkbox is unchecked, uncheck selectAll
                if (!this.checked) {
                    selectAll.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }

                updateHiddenInput();
            });
        });

        function updateHiddenInput() {
            const checkboxes = document.querySelectorAll('.custom-select-option');
            const selectedValues = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            const hiddenInput = document.getElementById('selectedValues');
            hiddenInput.value = JSON.stringify(selectedValues);
        }

        // Example showArray function with error handling
        function showArray() {
            const hiddenInput = document.getElementById('selectedValues');
            let selectedArray = [];

            try {
                // Only parse if the value exists and isn't empty
                if (hiddenInput.value) {
                    selectedArray = JSON.parse(hiddenInput.value);
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
                // Fallback to empty array
                selectedArray = [];
            }

            console.log(selectedArray);
            return selectedArray;
        }
    </script>
@endsection