<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexBannder\StoreIndexBannerRequest;
use App\Http\Requests\Admin\IndexBannder\UpdateIndexBannerRequest;
use App\Models\IndexBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class IndexBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $index_banners = IndexBanner::query()->latest()->paginate();
        return view('admin.index_banners.index', compact('index_banners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIndexBannerRequest $request)
    {
        \Log::info('IndexBanner Store - Request received', [
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_cover_image' => $request->hasFile('cover_image'),
            'all_input_keys' => array_keys($request->all()),
            'title' => $request->input('title'),
            'link' => $request->input('link'),
        ]);

        // Log file information if present
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            \Log::info('IndexBanner Store - File details', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
                'temp_path' => $file->getRealPath(),
            ]);
        } else {
            \Log::warning('IndexBanner Store - No cover_image file in request', [
                'files' => $request->allFiles(),
                'input' => $request->except(['_token']),
            ]);
        }

        // Log PHP upload settings
        \Log::info('IndexBanner Store - PHP Upload Settings', [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'file_uploads' => ini_get('file_uploads'),
        ]);

        try {
            $validated = $request->validated();
            \Log::info('IndexBanner Store - Validation passed', [
                'validated_keys' => array_keys($validated),
            ]);

            // Remove cover_image from validated data as it's handled separately via media library
            unset($validated['cover_image']);
            
            $banner = IndexBanner::query()->create($validated);
            \Log::info('IndexBanner Store - Banner created', [
                'banner_id' => $banner->id,
            ]);
            
            if ($request->hasFile('cover_image')) {
                try {
                    $file = $request->file('cover_image');
                    \Log::info('IndexBanner Store - Attempting to add media', [
                        'banner_id' => $banner->id,
                        'file_path' => $file->getRealPath(),
                        'file_size' => $file->getSize(),
                    ]);

                    $banner->clearMediaCollection('cover_image');
                    $media = $banner->addMedia($file)
                        ->toMediaCollection('cover_image');
                    
                    \Log::info('IndexBanner Store - Media added successfully', [
                        'banner_id' => $banner->id,
                        'media_id' => $media->id,
                        'media_path' => $media->getPath(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('IndexBanner Store - Error adding media', [
                        'banner_id' => $banner->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }
            
            return response()->json(['message' => 'با موفقیت انجام شد']);
        } catch (\Exception $e) {
            \Log::error('IndexBanner Store - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', 'cover_image']),
            ]);
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIndexBannerRequest $request, IndexBanner $index_banner)
    {
        \Log::info('IndexBanner Update - Request received', [
            'banner_id' => $index_banner->id,
            'has_cover_image' => $request->hasFile('cover_image'),
        ]);

        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            \Log::info('IndexBanner Update - File details', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
            ]);
        }

        try {
            $validated = $request->validated();
            // Remove cover_image from validated data as it's handled separately via media library
            unset($validated['cover_image']);
            
            $index_banner->update($validated);
            \Log::info('IndexBanner Update - Banner updated', [
                'banner_id' => $index_banner->id,
            ]);
            
            if ($request->hasFile('cover_image')) {
                try {
                    $index_banner->clearMediaCollection('cover_image');
                    $media = $index_banner->addMedia($request->file('cover_image'))
                        ->toMediaCollection('cover_image');
                    
                    \Log::info('IndexBanner Update - Media updated successfully', [
                        'banner_id' => $index_banner->id,
                        'media_id' => $media->id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('IndexBanner Update - Error updating media', [
                        'banner_id' => $index_banner->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
            
            return response()->json(['message' => 'با موفقیت انجام شد']);
        } catch (\Exception $e) {
            \Log::error('IndexBanner Update - Error', [
                'banner_id' => $index_banner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndexBanner $index_banner)
    {
        $index_banner->delete();
        return response()->json();
    }


    public function table()
    {
        $index_banners = IndexBanner::query()->latest()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($index_banners as $index_banner) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$index_banner->id}}" title="حذف نقش" action="{{route('index_banners.destroy', $index_banner->id)}}" title="{{$index_banner->title}}" />

                <x-modal.update id="modal-edit-{{$index_banner->id}}" title="ویرایش نقش" action="{{route('index_banners.update',$index_banner->id)}}" >
                     <x-form.input title="سوال"  name="question" :value="$index_banner->question" />
                    <x-form.input title="جواب"  name="answer" :value="$index_banner->answer" />
                </x-modal.update>
            BLADE,
                ['index_banner' => $index_banner, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'index_banners-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.index_banners'),
            'items' => $index_banners,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
