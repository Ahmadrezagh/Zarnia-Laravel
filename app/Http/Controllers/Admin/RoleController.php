<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class RoleController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next){
            if(auth()->user()->isSuperadmin() || (auth()->user()->isAdmin() && auth()->user()->can('role')) ){
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
        $permissions = Permission::query()->get();
        $roles = Role::query()->paginate();
        return view('admin.roles.index',compact('permissions','roles'));
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
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create($request->validated());
        if(isset($request->permissions) && $request->permissions){
            foreach ($request->permissions as $permission){
                $role->permissions()->attach($permission);
            }
        }
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
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->validated());
        $role->permissions()->sync($request->permissions);
        return response()->json(['message' => 'با موفقیت انجام شد']);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'با موفقیت انجام شد']);

    }

    public function table()
    {
        $permissions = Permission::query()->get();
        $roles = Role::query()->paginate();

        // Initialize slot content
        $slotContent = '';

        // Loop through users and render the Blade string for each
        foreach ($roles as $role) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$role->id}}" title="حذف نقش" action="{{route('roles.destroy', $role->id)}}" title="{{$role->title}}" />

                <x-modal.update id="modal-edit-{{$role->id}}" title="ویرایش نقش" action="{{route('roles.update',$role->id)}}" >
                    <x-form.input title="عنوان" value="{{$role->title}}"  name="title" />
                    <div class="row">
                        @foreach($permissions as $permission)
                            <x-form.check-input col="col-6" :title="$permission->title" name="permissions[]" :value="$permission->id" id="edit-{{$role->id}}-{{$permission->id}}"  checked="{{$role->hasThisPermission($permission)}}" />
                        @endforeach
                    </div>
                </x-modal.update>
            BLADE,
                ['role' => $role, 'permissions' => $permissions]
            );
        }

        return view('components.table', [
            'id' => 'roles-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.roles'),
            'items' => $roles,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
