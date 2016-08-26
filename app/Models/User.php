<?php

namespace App\Models;

use Despark\Cms\Admin\Traits\UploadImagesTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Conner\Tagging\Taggable;
use Despark\Cms\Admin\Traits\AdminImage;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Despark\Cms\Admin\Traits\AdminModelTrait;

class User extends Authenticatable implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use CanResetPassword;
    use Taggable;
    use AdminImage;
    use HasRoles;
    use AdminModelTrait;
    use UploadImagesTrait;

    public $identifier = 'user';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    protected $rules = [
        'name' => 'required',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6|max:20',
    ];

    protected $rulesUpdate = [
        'name' => 'required',
        'email' => 'required|email|unique:users,email,{id},id',
        'password' => 'min:6|max:20',
    ];

    public static $rulesProfileEdit = [
        'name' => 'required',
        'password' => 'min:6|max:20|confirmed',
        'password_confirmation' => 'min:6|max:20',
    ];
}
