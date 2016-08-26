<?php

namespace Despark\Cms\Admin\Traits;

use File;
use Intervention\Image\Facades\Image;

/**
 * Class AdminImage.
 */
trait AdminImage
{
    /**
     * @var int
     */
    public static $portrait = 1;
    /**
     * @var int
     */
    public static $landscape = 2;

    /**
     * @var array
     */
    public $dimensions = [
        '1' => [
            ['w' => 920, 'h' => 575],
            ['w' => 672, 'h' => 420, 'admin'],
        ],
        '2' => [
            ['w' => 690, 'h' => 920],
            ['w' => 226, 'h' => 300],
            ['w' => 263, 'h' => 350],
            ['w' => 345, 'h' => 460, 'admin'],
        ],
    ];

    /**
     * @param $orientation
     * @return mixed
     */
    public function getImageByOrientation($orientation)
    {
        return $this->images()->where('orientation', '=', $orientation)->first();
    }

    /**
     * @return mixed
     */
    public function images()
    {
        return $this->morphMany('Despark\Cms\Models\Image', 'imageable');
    }

    /**
     * @param $options
     */
    public function addImages($options)
    {
        $imageFields = $this->getFiledFromPost($options);

        if ($imageFields) {
            foreach ($imageFields as $imageName => $image) {
                $orientation = $options['image_orientation'][$imageName];

                if ($imageEntity = $this->images()->where('orientation', '=', $orientation)->first()) {
                    $imageEntity->delete();
                }

                $fileName = $this->uploadImage($image, $orientation);

                $this->images()->create(['file' => $fileName, 'orientation' => $orientation]);
            }
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function getFiledFromPost($data)
    {
        $images = [];

        foreach ($data as $name => $val) {
            if (stripos($name, 'images_upload_') !== false) {
                $images[$name] = $val;
            }
        }

        return $images;
    }

    /**
     * @param $image
     * @param $orientation
     * @return string
     */
    public function uploadImage($image, $orientation)
    {
        $fileExtension = $image->getClientOriginalExtension();
        $fileName = rtrim($image->getClientOriginalName(), '.'.$fileExtension);

        $fileName = str_slug($fileName, '_');

        $img = Image::make($image);

        // Save default
        $fileName = $fileName.'.'.$fileExtension;
        $img->save($this->recordDirectory().$fileName);

        // resize and crop image to all dimensions and save it
        foreach ($this->getDimensions($orientation) as $dimensions) {
            $img->fit($dimensions['w'], $dimensions['h'], function ($constraint) {
                $constraint->aspectRatio();
            })->save($this->recordDirectory().$this->fileNameByDimension($fileName, $dimensions));
        }

        return $fileName;
    }

    /**
     * @return array|mixed|string
     */
    public function recordDirectory()
    {
        $modelDir = explode('Models', get_class($this));
        $modelDir = str_replace('\\', '_', $modelDir[1]);
        $modelDir = ltrim($modelDir, '_');
        $modelDir = strtolower($modelDir).DIRECTORY_SEPARATOR;
        $modelDir = 'uploads'.DIRECTORY_SEPARATOR.$modelDir;

        if ( ! File::isDirectory(public_path($modelDir))) {
            File::makeDirectory(public_path($modelDir));
        }

        $modelDir = strtolower($modelDir).$this->id.DIRECTORY_SEPARATOR;

        if ( ! File::isDirectory(public_path($modelDir))) {
            File::makeDirectory(public_path($modelDir));
        }

        return $modelDir;
    }

    /**
     * @param $orientation
     * @return mixed
     */
    public function getDimensions($orientation)
    {
        return isset($this->modelDimensions) ? $this->modelDimensions[$orientation] : $this->dimensions[$orientation];
    }

    /**
     * @param $file
     * @param $dimensions
     * @return string
     */
    public function fileNameByDimension($file, $dimensions)
    {
        $nameAndExtension = $this->filenameAndExtension($file);

        return $nameAndExtension['fileName'].'__'.$dimensions['w'].'x'.$dimensions['h'].'.'.$nameAndExtension['extension'];
    }

    /**
     * @param $fileName
     * @return array
     */
    public function filenameAndExtension($fileName)
    {
        $_arr = explode('.', $fileName);
        $extension = end($_arr);

        // to be sure that we get right image name if there are more than one dots in the title
        // name in database is slugable so next row needs to be check, if it is useless needs to be removed
        $fileName = str_replace('.'.$extension, '', $fileName);

        return ['fileName' => $fileName, 'extension' => $extension];
    }

    /**
     * @param $image
     * @param $orientation
     * @return string
     */
    public function getAdminImageFile($image, $orientation)
    {
        foreach ($this->getDimensions($orientation) as $dimensions) {
            if (in_array('admin', $dimensions)) {
                $admin_dimensions = $dimensions;
            }
        }

        return $this->recordDirectory().$this->fileNameByDimension($image, $admin_dimensions);
    }

    /**
     * @param     $orientation
     * @param int $dimensionsKey
     * @return string
     */
    public function getFilePathByOrientation($orientation, $dimensionsKey = 0)
    {
        if ($imagePath = $this->imageFileByOrientation($orientation)) {
            return $this->recordDirectory().$this->fileNameByDimension($imagePath,
                $this->dimensions[$orientation][$dimensionsKey]);
        }
    }

    /**
     * @param $orientation
     * @return bool
     */
    public function imageFileByOrientation($orientation)
    {
        if ($this->images()->where('orientation', '=', $orientation)->first()) {
            return $this->images()->where('orientation', '=', $orientation)->first()->file;
        }

        return false;
    }

    /**
     * @param int    $orientation
     * @param string $dimension
     * @return mixed
     */
    public function getAdminDimensions($orientation = 1, $dimension = 'w')
    {
        foreach ($this->getDimensions($orientation) as $dimensions) {
            if (in_array('admin', $dimensions)) {
                $admin_dimensions = $dimensions;
            }
        }

        return $admin_dimensions[$dimension];
    }
}
