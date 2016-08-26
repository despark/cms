<?php
namespace Despark\Cms\Models;

use Despark\Cms\Admin\Traits\AdminModelTrait;
use Spatie\Permission\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    use AdminModelTrait;

    public $identifier = 'permission';
}