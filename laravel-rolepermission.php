User Table:
===========

public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('role_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }



  Module:
  =======

   public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });
    }



  Permission:
  ==========
   public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('module_id');
            $table->string('name');
            $table->string('slug')->unique();
            

            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');
            $table->timestamps();
            
        });
    }



Role:
=====

public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('deletable')->default(true);
            $table->timestamps();
        });
    }




Role-Permission:
===============

public function up()
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }




User Model:
==========

  protected $guarded = ['id'];



    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    public function hasPermission($permission): bool
    {
        return $this->role->permissions()->where('slug', $permission)->first() ? true : false;
    }



Role Model:
=========

 protected $guarded = ['id'];


  public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }



Permission Model:
================

protected $guarded = ['id'];

	
     public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }



  Module Model:
  =============

	protected $guarded = ['id'];

     public function permissions()
    {
        return $this->hasMany(Permission::class);
    }



Rolecontroller:
===============




<?php

namespace App\Http\Controllers\Backend;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\role;
use Illuminate\Http\Request;
use App\Module;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

         Gate::authorize('app.roles.index');
        $roles = Role::all();
       return view('backend.roles.index',compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

         Gate::authorize('app.roles.create');
        $modules = Module::all();
        return view('backend.roles.form',compact('modules'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        Gate::authorize('app.roles.create');
       Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ])->permissions()->sync($request->input('permissions', []));
        
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(role $role)
    {

         if (Gate::authorize('app.roles.edit')) {
       
         $modules = Module::all();
        return view('backend.roles.form',compact('role','modules'));
    }
    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, role $role)
    {



        Gate::authorize('app.roles.edit');
         $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);
        $role->permissions()->sync($request->input('permissions'));
        
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(role $role)
    {
        //
    }
}




AuthGates Middleware:
======================
<?php

namespace App\Http\Middleware;

use Closure;
use App\Permission;
use App\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
class AuthGates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user) {
            $permissions = Permission::all();
            foreach ($permissions as $key => $permission) {
                Gate::define($permission->slug, function (User $user) use ($permission) {
                    return $user->hasPermission($permission->slug);
                });
            }
        }
        return $next($request);
    }
}


Kernel:
======
 \App\Http\Middleware\AuthGates::class,













 @extends('layouts.backend.app')

@section('title','Roles')

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-check icon-gradient bg-mean-fruit">
                    </i>
                </div>
                <div>{{ isset($role) ? 'Edit' : 'Create New' }} Role</div>
            </div>
            <div class="page-title-actions">
                <div class="d-inline-block dropdown">
                    <a href="{{ url('app/roles') }}" class="btn-shadow btn btn-danger">
                        <span class="btn-icon-wrapper pr-2 opacity-7">
                            <i class="fas fa-arrow-circle-left fa-w-20"></i>
                        </span>
                        Back to list
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="main-card mb-3 card">
                <!-- form start -->
                <form id="roleFrom" role="form" method="POST"
                      action="{{ isset($role) ? url('app/roles',$role->id) : url('app/roles') }}">
                    @csrf
                    @if (isset($role))
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">Manage Roles</h5>

                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" id="name" name="name" class="form-control"  value="{{ $role->name ?? old('name')  }}">
                  </div>



        <div class="text-center">
            <strong>Manage permissions for role</strong>
                        
        </div>

<div class="form-group">
    <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="select-all">
                                <label class="custom-control-label" for="select-all">Select All</label>
                            </div>
                        </div>



 @forelse($modules->chunk(2) as $key => $chunks)
<div class="form-row">
    @foreach($chunks as $key=>$module)
        <div class="col">
            <h5>Module: {{ $module->name }}</h5>
            @foreach($module->permissions as $key=>$permission)
                <div class="mb-3 ml-4">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input"
                               id="permission-{{ $permission->id }}"
                               value="{{ $permission->id }}"
                               name="permissions[]"
                        @if(isset($role))
                            @foreach($role->permissions as $rPermission)
                            {{ $permission->id == $rPermission->id ? 'checked' : '' }}
                            @endforeach
                        @endif
                        >
                        <label class="custom-control-label"
                               for="permission-{{ $permission->id }}">{{ $permission->name }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
   @empty
<div class="row">
    <div class="col text-center">
        <strong>No Module Found.</strong>
    </div>
</div>
 @endforelse






                       

<button type="submit" class="btn btn-primary">
    @isset($role)
        <i class="fas fa-arrow-circle-up"></i>
        <span>Update</span>
    @else
        <i class="fas fa-plus-circle"></i>
        <span>Create</span>
    @endisset
</button>
                    </div>
                </form>
            </div>
            <!-- /.card -->
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
        // Listen for click on toggle checkbox
        $('#select-all').click(function (event) {
            if (this.checked) {
                // Iterate each checkbox
                $(':checkbox').each(function () {
                    this.checked = true;
                });
            } else {
                $(':checkbox').each(function () {
                    this.checked = false;
                });
            }
        });
    </script>

    
@endsection
















