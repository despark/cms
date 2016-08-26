<?php

Route::resource('users', 'Admin\UsersController', [
    'names' => [
        'index'   => 'admin.users.index',
        'store'   => 'admin.users.store',
        'create'  => 'admin.users.create',
        'update'  => 'admin.users.update',
        'show'    => 'admin.users.show',
        'destroy' => 'admin.users.destroy',
        'edit'    => 'admin.users.edit',
    ],
]);
Route::resource('seo_pages', 'Admin\SeoPagesController');
Route::resource('roles', 'Admin\RolesController');
Route::resource('permissions', 'Admin\PermissionsController');

Route::get('profile/edit', ['uses' => 'Admin\ProfileController@edit', 'as' => 'admin.profile.edit']);
Route::get('profile/update', ['uses' => 'Admin\ProfileController@update', 'as' => 'admin.profile.update']);
