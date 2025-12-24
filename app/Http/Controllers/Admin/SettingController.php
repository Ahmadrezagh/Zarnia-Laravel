<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Setting\UpdateSettingRequest;
use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SettingGroup $setting_group)
    {
        return view('admin.settings.index', compact('setting_group'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(SettingGroup $setting_group)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SettingGroup $setting_group)
    {
        if ($request->settings ){
           foreach ($request->settings as $key => $value ) {
               $setting = $setting_group->settings()->where('key', '=', $key)->first();
               
               if (!$setting) {
                   continue;
               }
               
               // Handle image_array type
               if ($setting->type === 'image_array') {
                   $images = [];
                   
                   // Get existing images from old input (preloaded images that should be kept)
                   $oldImagesInput = $request->input("old_{$key}");
                   if ($oldImagesInput) {
                       if (is_string($oldImagesInput)) {
                           $oldImages = json_decode($oldImagesInput, true) ?? [];
                       } else {
                           $oldImages = is_array($oldImagesInput) ? $oldImagesInput : [];
                       }
                       $images = array_merge($images, $oldImages);
                   }
                   
                   // Handle new file uploads
                   // Check for array format: settings[key][]
                   $uploadedFiles = [];
                   if ($request->hasFile("settings.{$key}")) {
                       $files = $request->file("settings.{$key}");
                       if (is_array($files)) {
                           $uploadedFiles = $files;
                       } else {
                           $uploadedFiles = [$files];
                       }
                   }
                   
                   // Also check for direct file input
                   if (empty($uploadedFiles) && $request->hasFile($key)) {
                       $files = $request->file($key);
                       if (is_array($files)) {
                           $uploadedFiles = $files;
                       } else {
                           $uploadedFiles = [$files];
                       }
                   }
                   
                   foreach ($uploadedFiles as $file) {
                       if ($file && $file->isValid()) {
                           $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                           $path = public_path('uploads/settings');
                           if (!file_exists($path)) {
                               mkdir($path, 0755, true);
                           }
                           $file->move($path, $filename);
                           $images[] = '/uploads/settings/' . $filename;
                       }
                   }
                   
                   // Limit to 2 images
                   $images = array_slice($images, 0, 2);
                   
                   // Update setting value as JSON
                   $setting->update(['value' => json_encode($images)]);
               } else {
                   // Handle other types
                   if ($value) {
                       $setting->update(['value' => $value]);
                   }
               }
           }
        }
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Display the specified resource.
     */
    public function show(SettingGroup $setting_group, Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SettingGroup $setting_group, Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSettingRequest $request, SettingGroup $setting_group, Setting $setting)
    {

       // return $request;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SettingGroup $setting_group, Setting $setting)
    {
        //
    }
    
    /**
     * Delete an image from image_array setting
     */
    public function deleteImage(Request $request, SettingGroup $setting_group, Setting $setting, $imageIndex)
    {
        $images = json_decode($setting->value, true) ?? [];
        
        if (isset($images[$imageIndex])) {
            $imagePath = $images[$imageIndex];
            
            // Delete physical file
            $fullPath = public_path($imagePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // Remove from array
            unset($images[$imageIndex]);
            $images = array_values($images); // Re-index array
            
            // Update setting
            $setting->update(['value' => json_encode($images)]);
            
            return response()->json(['message' => 'تصویر با موفقیت حذف شد', 'images' => $images]);
        }
        
        return response()->json(['message' => 'تصویر یافت نشد'], 404);
    }
}
