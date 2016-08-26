<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Http\Requests\RoleRequest;
use Despark\Cms\Http\Controllers\Admin\AdminController;

class RolesController extends AdminController
{
    /**
     * RolesController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->sidebarItems['users']['isActive'] = true;
        $this->sidebarItems['users']['subMenu']['roles']['isActive'] = true;

        $this->viewData['pageTitle'] = 'Roles';
        $this->viewData['editRoute'] = 'roles.edit';
        $this->viewData['createRoute'] = 'roles.create';
        $this->viewData['deleteRoute'] = 'roles.destroy';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $model = new Role();
        $records = $model->filtering()->paginate($this->paginateLimit);

        $this->viewData['model'] = $model;
        $this->viewData['records'] = $records;

        return view('admin.layouts.list', $this->viewData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $model = new Role();

        $this->viewData['record'] = $model;

        $this->viewData['actionVerb'] = 'Create';
        $this->viewData['formMethod'] = 'POST';
        $this->viewData['formAction'] = 'roles.store';

        return view($this->defaultFormView, $this->viewData);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function store(RoleRequest $request)
    {
        $input = $request->all();

        $model = new Role();

        $record = $model->create($input);

        $record->perms()->sync([]);
        $record->attachPermissions($request->get('permissions'));

        $this->notify([
            'type' => 'success',
            'title' => 'Successful create role!',
            'description' => 'Role is created successfully!',
        ]);

        return redirect(route('roles.edit', ['id' => $record->id]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $record = Role::findOrFail($id);

        $this->viewData['record'] = $record;

        $this->viewData['formMethod'] = 'PUT';
        $this->viewData['formAction'] = 'roles.update';

        return view($this->defaultFormView, $this->viewData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function update(RoleRequest $request, $id)
    {
        $input = $request->all();

        $record = Role::findOrFail($id);

        $record->perms()->sync([]);
        $record->attachPermissions($request->get('permissions'));

        $record->update($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful update!',
            'description' => 'This role is updated successfully.',
        ]);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        Role::findOrFail($id)->delete();

        $this->notify([
            'type' => 'danger',
            'title' => 'Successful deleted Role!',
            'description' => 'The role is deleted successfully.',
        ]);

        return redirect()->back();
    }
}
