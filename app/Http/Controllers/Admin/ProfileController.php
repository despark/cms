<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserEditProfileRequest;
use Despark\Cms\Http\Controllers\Admin\AdminController;

class ProfileController extends AdminController
{
    public function edit()
    {
        $this->viewData['record'] = \Auth::user();

        $this->viewData['pageTitle'] = 'Edit profile';
        $this->viewData['formMethod'] = 'PUT';
        $this->viewData['formAction'] = 'admin.users.update';

        return view($this->defaultFormView, $this->viewData);
    }

    public function update(UserEditProfileRequest $request)
    {
        $input = $request->all();

        $record = \Auth::user();

        $record->update($input);

        $record->addImages($input);

        $this->notify([
            'type' => 'success',
            'title' => 'Successful update!',
            'description' => 'Profile is updated successfully.',
        ]);

        return redirect()->back();
    }
}
