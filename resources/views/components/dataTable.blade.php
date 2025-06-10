@props([
    'hasCheckbox' => false,
    'id' => 'datatable',
    'url' => '',
    'columns' => [],
    'actions' => [],
])

<div class="table-responsive">
    <input type="hidden" id="selectedValues" value="[]">
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
        <tbody></tbody>
    </table>
</div>

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
@endsection

@section('js')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('#{{ $id }}').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ $url }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error, thrown);
                        alert('Failed to load data. Please try again.');
                    }
                },
                columns: [
                        @if($hasCheckbox)
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `<input type="checkbox" class="form-control custom-select-option" value="${data}" />`;
                        }
                    },
                        @endif
                        @foreach($columns as $column)
                    {
                        data: '{{ $column['key'] }}',
                        name: '{{ $column['key'] }}',
                        render: function(data, type, row) {
                            @if(isset($column['route']))
                            let url = '{{ route($column['route'][0], array_fill(0, count($column['route'][1]), ':param')) }}';
                            @foreach($column['route'][1] as $key => $value)
                            const paramKey = '{{ trim($value, '{}') }}';
                            url = url.replace(':param', row[paramKey] || '');
                            @endforeach
                                return `<a href="${url}" target="_blank">${data || ''}</a>`;
                            @elseif(isset($column['url']))
                            let url = '{{ $column['url'] }}';
                            // Dynamically replace placeholders with row data
                            for (const key in row) {
                                if (row.hasOwnProperty(key)) {
                                    url = url.replace(`{${key}}`, row[key] || '');
                                }
                            }
                            return `<a href="${url}" target="_blank">${data || ''}</a>`;
                            @elseif($column['type'] === 'image')
                                return data ? `<img src="${data}" style="width: 100px; height: 100px; border-radius: 50%" alt="Image">` : '';
                            @elseif($column['type'] === 'copiableText')
                                return `<x-form.copiable-component content="${data || ''}" />`;
                            @elseif($column['type'] === 'binaryCondition')
                                return data == '1'
                                ? `<button class="btn btn-success">{{ $column['texts']['true'] }}</button>`
                                : `<button class="btn btn-danger">{{ $column['texts']['false'] }}</button>`;
                            @else
                                return data || '';
                            @endif
                        }
                    },
                        @endforeach
                        @if($actions)
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let actionsHtml = `
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                                            عملیات
                                        </button>
                                        <div class="dropdown-menu">
                                `;
                            @foreach($actions as $action)
                            @if(isset($action['route']))
                            let url = '{{ route($action['route'][0], array_fill(0, count($action['route'][1]), ':param')) }}';
                            @foreach($action['route'][1] as $key => $value)
                            const paramKey = '{{ trim($value, '{}') }}';
                            url = url.replace(':param', row[paramKey] || '');
                            @endforeach
                                actionsHtml += `<a href="${url}" class="dropdown-item" target="_blank">{{ $action['label'] }}</a>`;
                            @elseif(isset($action['url']))
                            let url = '{{ $action['url'] }}';
                            // Dynamically replace placeholders with row data
                            for (const key in row) {
                                if (row.hasOwnProperty(key)) {
                                    url = url.replace(`{${key}}`, row[key] || '');
                                }
                            }
                            actionsHtml += `<a href="${url}" class="dropdown-item" target="_blank">{{ $action['label'] }}</a>`;
                            @else
                                actionsHtml += `<button class="dropdown-item" data-toggle="modal" data-target="#{{ $action['type'] }}-${row.id}">{{ $action['label'] }}</button>`;
                            @endif
                                    @endforeach
                                actionsHtml += `</div></div>`;
                            return actionsHtml;
                        }
                    }
                    @endif
                ],
                language: {
                    processing: 'در حال بارگذاری...',
                    emptyTable: 'داده‌ای در دسترس نیست',
                    info: 'نمایش _START_ تا _END_ از _TOTAL_ مورد',
                    infoEmpty: 'نمایش 0 تا 0 از 0 مورد',
                    infoFiltered: '(فیلتر شده از _MAX_ مورد)',
                    lengthMenu: 'نمایش _MENU_ مورد در هر صفحه',
                    search: 'جستجو:',
                    zeroRecords: 'هیچ رکوردی یافت نشد',
                    paginate: {
                        first: 'اولین',
                        last: 'آخرین',
                        next: 'بعدی',
                        previous: 'قبلی'
                    }
                }
            });

            // Checkbox handling
            $('#selectAll').on('click', function() {
                const isChecked = this.checked;
                table.$('.custom-select-option').prop('checked', isChecked);
                updateHiddenInput();
            });

            table.on('change', '.custom-select-option', function() {
                const allCheckboxes = table.$('.custom-select-option');
                $('#selectAll').prop('checked', allCheckboxes.length === table.$('.custom-select-option:checked').length);
                updateHiddenInput();
            });

            function updateHiddenInput() {
                const selectedValues = table.$('.custom-select-option:checked').map(function() {
                    return this.value;
                }).get();
                $('#selectedValues').val(JSON.stringify(selectedValues));
            }

            window.showArray = function() {
                try {
                    const selectedArray = JSON.parse($('#selectedValues').val() || '[]');
                    console.log(selectedArray);
                    return selectedArray;
                } catch (error) {
                    console.error('Error parsing selected values:', error);
                    return [];
                }
            };
        });
    </script>
@endsection