<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\PermissionRequest;
use Despark\Cms\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Response;
use Spatie\Permission\Contracts\Permission;

class PermissionsController extends AdminController
{
    /**
     * @var Permission|\Despark\Cms\Models\Permission
     */
    protected $permissions;

    /**
     * PermissionsController constructor.
     * @param Permission $permission
     */
    public function __construct(Permission $permission)
    {
        parent::__construct();

        $this->sidebarItems['users']['isActive'] = true;
        $this->sidebarItems['users']['subMenu']['permissions']['isActive'] = true;

        $this->viewData['pageTitle'] = 'Permissions';
        $this->viewData['editRoute'] = 'permissions.edit';
        $this->viewData['createRoute'] = 'permissions.create';
        $this->viewData['deleteRoute'] = 'permissions.destroy';

        $this->permissions = $permission;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $records = $this->permissions->paginate($this->paginateLimit);

        $this->viewData['model'] = $this->permissions;
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
        $this->viewData['record'] = $this->permissions;

        $this->viewData['actionVerb'] = 'Create';
        $this->viewData['formMethod'] = 'POST';
        $this->viewData['formAction'] = 'permissions.store';

        return view($this->defaultFormView, $this->viewData);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $record = $this->permissions->create($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful create permission!',
            'description' => 'Permission is created successfully!',
        ]);

        return redirect(route('permissions.edit', ['id' => $record->id]));
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
        $record = $this->permissions->findOrFail($id);

        $this->viewData['record'] = $record;

        $this->viewData['formMethod'] = 'PUT';
        $this->viewData['formAction'] = 'permissions.update';

        return view($this->defaultFormView, $this->viewData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param PermissionRequest $request
     * @param int                       $id
     * @return Response
     */
    public function update(PermissionRequest $request, $id)
    {
        $input = $request->all();

        $record = $this->permissions->findOrFail($id);

        $record->update($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful update!',
            'description' => 'This permission is updated successfully.',
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
        $this->permissions->findOrFail($id)->delete();

        $this->notify([
            'type' => 'danger',
            'title' => 'Successful deleted Permission!',
            'description' => 'The permission is deleted successfully.',
        ]);

        return redirect()->back();
    }
}
