@extends('layouts.panel')
@section('content')


    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="main-content-name tx-24 mg-b-5">تنظیمات</h2>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">خانه</a></li>
                <li class="breadcrumb-item active" aria-current="page">تنظیمات</li>
            </ol>
        </div>
        <div class="d-flex">
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Row -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card custom-card overflow-hidden">
                <div class="card-header">
                    <h5>
                        {{$setting_group->title}}
                    </h5>
                </div>
                <form action="{{ route('setting_group.settings.store', $setting_group->id) }}" method="POST" class="ajax-form" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        @foreach($setting_group->settings as $setting)
                            <div class="form-group">
                                <label>
                                    {{ $setting->title }}
                                </label>
                                @if($setting->type == 'string')
                                    <input class="form-control" name="settings[{{$setting->key}}]" type="text" value="{{ $setting->value }}">

                                @elseif($setting->type == 'numeric')
                                    <input class="form-control" name="settings[{{$setting->key}}]" type="number" value="{{ $setting->value }}">

                                @elseif($setting->type == 'email')
                                    <input class="form-control" name="settings[{{$setting->key}}]" type="email" value="{{ $setting->value }}">

                                @elseif($setting->type == 'tel')
                                    <input class="form-control" name="settings[{{$setting->key}}]" type="tel" value="{{ $setting->value }}">

                                @elseif($setting->type == 'textarea')
                                    <textarea name="settings[{{$setting->key}}]" class="form-control froala" >{{ $setting->value }}</textarea>

                                @elseif($setting->type == 'file')
                                    <div class="custom-file">
                                        <input type="file" name="settings[{{$setting->key}}]" class="custom-file-input" id="customFile">
                                        <label class="custom-file-label" for="customFile">Choose file</label>
                                    </div>

                                @elseif($setting->type == 'image_array')
                                    @php
                                        $images = json_decode($setting->value, true) ?? [];
                                        $preloadedImages = [];
                                        foreach ($images as $index => $img) {
                                            $preloadedImages[] = [
                                                'id' => $index,
                                                'src' => asset($img)
                                            ];
                                        }
                                    @endphp
                                    <div id="image-uploader-{{ $setting->key }}"></div>
                                    <input type="hidden" name="old_{{ $setting->key }}" value="{{ json_encode($images) }}" id="old-images-{{ $setting->key }}">
                                    <script>
                                        $(document).ready(function() {
                                            var preloadedImages = @json($preloadedImages);
                                            
                                            $('#image-uploader-{{ $setting->key }}').imageUploader({
                                                label: 'تصاویر را انتخاب کنید یا اینجا بکشید و رها کنید (حداکثر ۲ تصویر)',
                                                imagesInputName: 'settings[{{ $setting->key }}]',
                                                preloadedInputName: 'old_{{ $setting->key }}',
                                                maxFiles: 2,
                                                maxSize: 2 * 1024 * 1024,
                                                preloaded: preloadedImages,
                                                extensions: ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.JPG', '.JPEG', '.webp', '.WEBP'],
                                                mimes: ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']
                                            });
                                            
                                            // Handle image deletion via delete button (for preloaded images)
                                            $(document).on('click', '#image-uploader-{{ $setting->key }} .uploaded-image[data-preloaded="true"] .delete-image', function(e) {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                var $image = $(this).closest('.uploaded-image');
                                                // Get image ID from preloaded input
                                                var $preloadedInput = $image.find('input[name="old_{{ $setting->key }}[]"]');
                                                var imageId = $preloadedInput.length ? $preloadedInput.val() : $image.index();
                                                
                                                if (confirm('آیا از حذف این تصویر اطمینان دارید؟')) {
                                                    $.ajax({
                                                        url: '{{ route("setting_group.settings.deleteImage", [$setting_group->id, $setting->id, ":index"]) }}'.replace(':index', imageId),
                                                        method: 'DELETE',
                                                        headers: {
                                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                        },
                                                        success: function(response) {
                                                            // Remove image from DOM
                                                            $image.fadeOut(300, function() {
                                                                $(this).remove();
                                                            });
                                                            // Update hidden input with remaining images
                                                            $('#old-images-{{ $setting->key }}').val(JSON.stringify(response.images));
                                                            toastr.success(response.message);
                                                        },
                                                        error: function() {
                                                            toastr.error('خطا در حذف تصویر');
                                                        }
                                                    });
                                                }
                                            });
                                        });
                                    </script>
                                @endif

                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            ویرایش
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Row -->

@endsection
