<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use function Laravel\Prompts\password;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next){
            if(auth()->user()->isSuperadmin() || (auth()->user()->isAdmin() && auth()->user()->can('user')) ){
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
        $users = User::query()->users()->paginate();
        return view('admin.users.index',compact('users'));
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
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        if ($request->profile_image){
            $validated['profile_image'] = upload_file($request->profile_image, '/profiles');
        }
        $user = User::create($validated);
        $user ->update(['type' => User::$TYPES[2]]);
        
        // Return user object for AJAX requests (e.g., from order creation modal)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'با موفقیت انجام شد',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ]
            ]);
        }
        
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = \App\Models\User::with('addresses')->findOrFail($id);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'addresses' => $user->addresses->map(function($address) {
                return [
                    'id' => $address->id,
                    'receiver_name' => $address->receiver_name,
                    'address' => $address->address,
                    'phone' => $address->phone ?? $address->receiver_phone,
                ];
            })
        ]);
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
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        if ($request->profile_image){
            $validated['profile_image'] = upload_file($request->profile_image, '/profiles');
        }
        if($validated['password'] == null){
            unset($validated['password']);
        }
        $user->update($validated);
        $user->roles()->sync($request->roles);
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);
    }

    public function table()
    {
        $users = User::query()->users()->paginate();
        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($users as $user) {
            $slotContent .= Blade::render(
                <<<'BLADE'
                  <x-modal.destroy id="modal-destroy-{{$user->id}}" title="حذف کاربر" action="{{route('users.destroy', $user->id)}}" title="{{$user->name}}" />
                <!-- Modal -->
                <x-modal.update id="modal-edit-{{$user->id}}" title="ویرایش کاربر" action="{{route('users.update', $user->id)}}" >
                    <input type="hidden" name="id" value="{{$user->id}}">
                    <x-form.input title="نام" :value="$user->name" name="name" />
                    <x-form.file-input title="عکس" name="profile_image" />
                    <x-form.input title="ایمیل" :value="$user->email" name="email" type="email" />
                    <x-form.input title="شماره تماس" :value="$user->phone" name="phone" type="tel" />
                    <x-form.input title="رمز عبور"  name="password" type="password" />
                    <x-form.input title="تکرار رمز عبور"  name="password" type="password_confirmation" />
                </x-modal.update>
                <!-- /Modal -->
            BLADE,
                ['user' => $user]
            );
        }
        return view('components.table', [
            'id' => 'users-table',
            'columns' => [
                ['label' => 'تصویر پروفایل', 'key' => 'profile_image', 'type' => 'image'],
                ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
                ['label' => 'شماره تماس', 'key' => 'phone', 'type' => 'text'],
            ],
            'url' => route('table.users'),
            'items' => $users,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
