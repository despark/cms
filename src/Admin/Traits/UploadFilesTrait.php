<?php

namespace Despark\Cms\Admin\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use File;
use Illuminate\Support\Facades\Request;

/**
 * Class UploadFilesTrait
 * @package Despark\Cms\Admin\Traits
 */
trait UploadFilesTrait
{
    /**
     * @var string
     */
    public $uploadFileDir = 'uploads';

    /**
     *
     */
    public function saveFiles()
    {
        $fileFields = $this->getFileFields();

        foreach ($fileFields as $fieldName => $options) {
            $file = Request::file($fieldName);
            if ($file instanceof UploadedFile && $file->isValid()) {
                $filename = $file->getClientOriginalName();
                $fileSavePath = $this->getFileSavePath($options['dirName']);

                $file->move($fileSavePath, $filename);

                $this->attributes[$fieldName] = $fileSavePath.$filename;
            }
        }
    }

    /**
     * @return array|mixed|string
     */
    private function getFileCurrentUploadDir()
    {
        $modelDir = explode('Models', get_class($this));
        $modelDir = str_replace('\\', '_', $modelDir[1]);
        $modelDir = ltrim($modelDir, '_');
        $modelDir = strtolower($modelDir);

        $modelDir = $this->uploadFileDir.DIRECTORY_SEPARATOR.$modelDir;

        return $modelDir;
    }

    /**
     * @param $dirName
     * @return string
     */
    private function getFileSavePath($dirName)
    {
        return $this->getFileCurrentUploadDir().DIRECTORY_SEPARATOR.$dirName.DIRECTORY_SEPARATOR;
    }

    /**
     * @return mixed
     */
    public function getFileFields()
    {
        return config('admin.'.$this->identifier.'.file_fields');
    }
}
