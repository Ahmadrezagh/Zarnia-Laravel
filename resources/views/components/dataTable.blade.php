@props([
    'hasCheckbox' => false,
    'id' => 'datatable',
    'url' => '',
    'columns' => [],
    'actions' => [],
    'perPage' => 10,
])

<div class="table-responsive" style="overflow-x: auto;">
    <input type="hidden" id="selectedValues" value="[]">
    <table class="table table-bordered" id="{{ $id }}" data-url="{{ $url }}">
        <thead>
        <tr>
            @if($hasCheckbox)
                <th><input type="checkbox" class="form-control" id="selectAll"></th>
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

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-body p-0 position-relative">
                    <button type="button" class="close text-white position-absolute" style="top: 10px; right: 15px; font-size: 2rem;" data-dismiss="modal" aria-label="Close">
                        &times;
                    </button>
                    <img id="modalImage" src="" alt="Preview" style="object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
</div>


@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
@endsection


@section('js')
    <script>
        $(document).ready(function () {
            const tableKey = '{{ $id }}';
            const storedPerPage = localStorage.getItem(`datatable_per_page_${tableKey}`);
            const defaultPerPage = storedPerPage ? parseInt(storedPerPage) : {{ $perPage }};

            const table = $('#{{ $id }}').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                pageLength: defaultPerPage,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [],
                ajax: {
                    url: '{{ $url }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    error: function (xhr, error, thrown) {
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
                        render: data => `<input type="checkbox" class="form-control custom-select-option" value="${data}" />`
                    },
                        @endif

                        @foreach($columns as $column)
                    {
                        data: '{{ $column['key'] }}',
                        name: '{{ $column['key'] }}',
                        render: function(data, type, row, meta) {
                            @if(isset($column['type']) && $column['type'] === 'ajax')
                                return `<span class="ajax-cell" data-row-index="${meta.row}" data-url-template="{{ route($column['route'], ':param') }}" data-param="${row['{{ $column['ajax_key'] }}']}">در حال بارگذاری...</span>`;
                            @elseif(isset($column['route']))
                            let url = '{{ route($column['route'][0], array_fill(0, count($column['route'][1]), ':param')) }}';
                            @foreach($column['route'][1] as $key => $value)
                            const paramKey = '{{ trim($value, '{}') }}';
                            url = url.replace(':param', row[paramKey] || '');
                            @endforeach
                                return `<a href="${url}" target="_blank">${data || ''}</a>`;
                            @elseif(isset($column['url']))
                            let url = '{{ $column['url'] }}';
                            for (const key in row) {
                                url = url.replace(`{${key}}`, row[key] || '');
                            }
                            return `<a href="${url}" target="_blank">${data || ''}</a>`;
                            @elseif($column['type'] === 'image')
                                return data ? `<img src="${data}" style="width: 100px; height: 100px; border-radius: 50%" alt="Image" onclick="previewImage('${data}')">` : '';
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
                            let actionsHtml = `<div class="btn-group"><button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">عملیات</button><div class="dropdown-menu">`;
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
                            for (const key in row) {
                                url = url.replace(`{${key}}`, row[key] || '');
                            }
                            actionsHtml += `<a href="${url}" class="dropdown-item" target="_blank">{{ $action['label'] }}</a>`;
                            @else
                                actionsHtml += `<button class="dropdown-item" data-toggle="modal" data-target="#{{ $action['type'] }}-${row.id}" onClick="{{ $action['type'] }}(${row.id})">{{ $action['label'] }}</button>`;
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

            // Load AJAX cells after draw
            table.on('draw.dt', function () {
                $('.ajax-cell').each(function () {
                    const span = $(this);
                    const urlTemplate = span.data('url-template');
                    const param = span.data('param');

                    if (!urlTemplate || !param) return;

                    const url = urlTemplate.replace(':param', encodeURIComponent(param));
                    fetch(url)
                        .then(res => res.text())
                        .then(html => span.html(html))
                        .catch(err => {
                            console.error('Failed to load ajax cell:', err);
                            span.html('<span class="text-danger">خطا</span>');
                        });
                });
            });

            // Search only on Enter
            $('#{{ $id }}_filter input').unbind().on('keypress', function (e) {
                if (e.which === 13) {
                    table.search(this.value).draw();
                }
            });

            // Save per-page
            table.on('length.dt', function (e, settings, len) {
                localStorage.setItem(`datatable_per_page_${tableKey}`, len);
            });

            // Checkbox handling
            $('#selectAll').on('click', function () {
                table.$('.custom-select-option').prop('checked', this.checked);
                updateHiddenInput();
            });

            table.on('change', '.custom-select-option', function () {
                const all = table.$('.custom-select-option');
                $('#selectAll').prop('checked', all.length === table.$('.custom-select-option:checked').length);
                updateHiddenInput();
            });

            function updateHiddenInput() {
                const selected = table.$('.custom-select-option:checked').map(function () {
                    return this.value;
                }).get();
                $('#selectedValues').val(JSON.stringify(selected));
            }

            window.showArray = function () {
                try {
                    return JSON.parse($('#selectedValues').val() || '[]');
                } catch (e) {
                    console.error(e);
                    return [];
                }
            };

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            function previewImage(src) {
                document.getElementById('modalImage').src = src;
                $('#imagePreviewModal').modal('show');
            }

            window.previewImage = previewImage;


            window.loadDataWithNewUrl = function(newUrl) {
                table.ajax.url(newUrl).load();
            };
        });
    </script>
@endsection
