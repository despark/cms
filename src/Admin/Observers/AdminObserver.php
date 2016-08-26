<?php


namespace Despark\Cms\src\Admin\Observers;


use Despark\Cms\Admin\Traits\UploadFilesTrait;
use Despark\Cms\Admin\Traits\UploadImagesTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminObserver
 * @package Despark\Cms\src\Admin\Observers
 */
class AdminObserver
{
    /**
     * @param Model $model
     */
    public function saving(Model $model)
    {
        if ($model instanceof UploadImagesTrait) {
            $model->saveImages();
        }
        if ($model instanceof UploadFilesTrait) {
            $model->saveFiles();
        }
    }
}