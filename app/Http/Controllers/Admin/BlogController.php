<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Blog\StoreBlogRequest;
use App\Http\Requests\Api\V1\Blog\UpdateBlogRequest;
use App\Models\AttributeGroup;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::query()->latest()->paginate();
        return view('admin.blogs.index', compact('blogs'));
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
    public function store(StoreBlogRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            unset($validated['slug']);
        }

        $blog = Blog::create($validated);
        if ($request->hasFile('cover_image')) {
            $blog->clearMediaCollection('cover_image');
            $blog->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        return response()->json(['message' => 'با موفقیت انجام شد']);
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
    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        $blog->update($request->validated());
        if ($request->hasFile('cover_image')) {
            $blog->clearMediaCollection('cover_image');
            $blog->addMedia($request->file('cover_image'))
                ->toMediaCollection('cover_image');
        }
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        $blog->delete();
        return response()->json();
    }


    public function table()
    {
        $blogs = Blog::query()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($blogs as $blog) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                 <!-- Modal -->
                <x-modal.destroy id="modal-destroy-{{$blog->id}}" title="حذف وبلاگ" action="{{route('blogs.destroy', $blog->slug)}}" title="{{$blog->title}}" />

                <x-modal.update id="modal-edit-{{$blog->id}}" title="ویرایش وبلاگ" action="{{route('blogs.update',$blog->slug)}}" >
                    <x-form.input title="عنوان"  name="title" :value="$blog->title" />
                    <div class="form-group">
                        <label for="blog-slug-edit-{{$blog->id}}">نامک (Slug)</label>
                        <input type="text" id="blog-slug-edit-{{$blog->id}}" name="slug" class="form-control" dir="ltr" value="{{$blog->slug}}" required>
                    </div>
                    <x-form.input title="عنوان متا (Meta Title)" name="meta_title" :value="$blog->meta_title" />
                    <x-form.textarea title="توضیحات متا (Meta Description)" name="meta_description" :value="$blog->meta_description" />
                    <x-form.textarea title="کلمات کلیدی متا (Meta Keywords)" name="meta_keywords" :value="$blog->meta_keywords" />
                    <x-form.textarea title="متن" name="description" :value="$blog->description" />
                    <x-form.file-input title="تصویر کاور" name="cover_image" />
                </x-modal.update>
            BLADE,
                ['blog' => $blog]
            );
        }
        return view('components.table', [
            'id' => 'blogs-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.blogs'),
            'items' => $blogs,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
