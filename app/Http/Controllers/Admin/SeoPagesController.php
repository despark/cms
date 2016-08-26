<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\SeoPage;
use App\Http\Requests\Admin\SeoPagesRequest;
use Despark\Cms\Http\Controllers\Admin\AdminController;

class SeoPagesController extends AdminController
{
    public function __construct()
    {
        $this->identifier = 'seo_page';

        parent::__construct();

        $this->sidebarItems['seo_pages']['isActive'] = true;
        $this->viewData['createRoute'] = 'seo_pages.create';
        $this->viewData['editRoute'] = 'seo_pages.edit';
        $this->viewData['deleteRoute'] = 'seo_pages.destroy';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $model = new SeoPage();
        $records = $model->get();

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
        $model = new SeoPage();

        $this->viewData['record'] = $model;

        $this->viewData['actionVerb'] = 'Create';
        $this->viewData['formMethod'] = 'POST';
        $this->viewData['formAction'] = 'seo_pages.store';

        return view($this->defaultFormView, $this->viewData);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function store(SeoPagesRequest $request)
    {
        $input = $request->all();

        $model = new SeoPage();

        $record = $model->create($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful create!',
            'description' => 'SeoPage is created successfully!',
        ]);

        return redirect(route('seo_pages.edit', ['id' => $record->id]));
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
        $record = SeoPage::findOrFail($id);

        $this->viewData['record'] = $record;

        $this->viewData['formMethod'] = 'PUT';
        $this->viewData['formAction'] = 'seo_pages.update';

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
    public function update(SeoPagesRequest $request, $id)
    {
        $input = $request->all();

        $record = SeoPage::findOrFail($id);

        $record->update($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful update!',
            'description' => 'SeoPage is updated successfully.',
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
        SeoPage::findOrFail($id)->delete();

        $this->notify([
            'type' => 'danger',
            'title' => 'Successful delete!',
            'description' => 'SeoPage is deleted successfully.',
        ]);

        return redirect()->back();
    }
}
