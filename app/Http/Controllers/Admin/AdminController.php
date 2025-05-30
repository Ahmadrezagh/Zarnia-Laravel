<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\Admin\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next){
            if(auth()->user()->isSuperadmin() || (auth()->user()->isAdmin() && auth()->user()->can('admin')) ){
                return $next($request);
            }
            else {
                abort(404);
            }
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::query()->admins()->paginate();
        $roles = Role::query()->get();
        return view('admin.admins.index',compact('users', 'roles'));
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
     *
     *
     */
    public function store(StoreAdminRequest $request)
    {
        $validated = $request->validated();
        if ($request->profile_image){
            $validated['profile_image'] = upload_file($request->profile_image, '/profiles');
        }
        $admin = User::create($validated);
        $admin->update(['type' => User::$TYPES[1]]);
        return response()->json(['message' => 'با موفقیت انجام شد']);
//
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
    public function update(UpdateAdminRequest $request, User $admin)
    {
        $validated = $request->validated();
        if ($request->profile_image){
            $validated['profile_image'] = upload_file($request->profile_image, '/profiles');
        }
        if($validated['password'] == null){
            unset($validated['password']);
        }
        $admin->update($validated);
        if ($request->roles > 0) {
            $admin->roles()->sync([$request->roles]);
        }else{
            $admin->roles()->detach();
        }
        return response()->json(['message' => 'با موفقیت انجام شد']);


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $admin)
    {
        $admin->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);

    }


    public function table()
    {
        $users = User::query()->admins()->paginate();
        $roles = Role::all(); // Fetch roles

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($users as $user) {
            $slotContent .= Blade::render(
                <<<'BLADE'
            <x-modal.destroy id="modal-destroy-{{ $user->id }}" title="حذف مدیر" action="{{ route('admins.destroy', $user->id) }}" title="{{ $user->name }}" />
            <!-- Modal -->
            <x-modal.update id="modal-edit-{{ $user->id }}" title="ویرایش مدیر" action="{{ route('admins.update', $user->id) }}" >
                <input type="hidden" name="id" value="{{ $user->id }}">
                <x-form.input title="نام" :value="$user->name" name="name" />
                <x-form.file-input title="عکس" name="profile_image" />
                <x-form.input title="ایمیل" :value="$user->email" name="email" type="email" />
                <x-form.input title="شماره تماس" :value="$user->phone" name="phone" type="number" />
                <x-form.input title="رمز عبور" name="password" type="password" />
                <x-form.input title="تکرار رمز عبور" name="password_confirmation" type="password" />
                <x-form.select-option title="نقش" name="roles">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @if($user->hasRole($role)) selected @endif>{{ $role->title }}</option>
                    @endforeach
                </x-form.select-option>
            </x-modal.update>
            BLADE,
                ['user' => $user, 'roles' => $roles]
            );
        }

        return view('components.table', [
            'id' => 'admins-table',
            'columns' => [
                ['label' => 'تصویر پروفایل', 'key' => 'profile_image', 'type' => 'image'],
                ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
            ],
            'url' => route('table.admins'),
            'items' => $users,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
