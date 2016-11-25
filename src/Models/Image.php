<?php

namespace Despark\Cms\Models;

use Despark\Cms\Admin\Interfaces\UploadImageInterface;
use Despark\Cms\Contracts\ImageContract;
use Despark\Cms\Exceptions\ImageFieldCollisionException;
use Despark\Cms\Observers\ImageModelObserver;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Image.
 */
class Image extends Model implements ImageContract
{
    /**
     * @var array Cache of generated thumb paths
     */
    protected $thumbnailPaths;

    /**
     * @var
     */
    protected $imageBaseName;

    /**
     * @var AdminModel|UploadImageInterface
     */
    protected $resourceModelInstance;

    /**
     * @var string
     */
    public $uploadDir = 'uploads';

    /**
     * @var
     */
    protected $currentUploadDir;

    /**
     * @var array
     */
    protected $imageAttributeFields = ['alt', 'title'];

    /**
     * @var array
     */
    protected $fillable = [
        'image_type',
        'original_image',
        'retina_factor',
        'order',
        'meta',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @var array
     */
    protected $rules = [
        'file' => 'image|max:5000',
    ];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @var
     */
    protected $dbColumns;

    /**
     * @var string
     */
    protected $cacheKey = 'igni_image';

    /**
     * Boot model.
     */
    public static function boot()
    {
        parent::boot();
        static::observe(ImageModelObserver::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function image()
    {
        return $this->morphTo('image', 'resource_model', 'resource_id');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAllImages()
    {
        $images = [];

        $images['__source__'] = [$this->getSourceImagePath()];

        $images['original'] = [
            'retina' => $this->getRetinaImagePath(),
            'original' => $this->getOriginalImagePath(),
        ];

        $imageFields = $this->getResourceModel()->getImageFields();

        // Check if image type is not admin_field
        $imageType = $this->image_type;

        if ($formField = $this->getResourceModel()->getFormField($imageType)) {
            // we need to check if it's gallery
            if ($formField['type'] == 'gallery' && isset($formField['image_field'])) {
                $imageType = $formField['image_field'];
            }
        }

        if (isset($imageFields[$imageType]['thumbnails'])) {
            foreach ($imageFields[$imageType]['thumbnails'] as $type => $options) {
                $images[$type] = [
                    'retina' => $this->getRetinaImagePath($type),
                    'original' => $this->getOriginalImagePath($type),
                ];
            }
        }

        return $images;
    }

    /**
     * @return array
     */
    public function getThumbnails()
    {
        $imageFields = $this->getResourceModel()->getImageFields();
        $thumbnails = [];
        if (isset($imageFields[$this->image_type]['thumbnails'])) {
            $thumbnails = array_keys($imageFields[$this->image_type]['thumbnails']);
        }

        return $thumbnails;
    }

    /**
     * @return AdminModel|UploadImageInterface
     * @throws \Exception
     */
    public function getResourceModel()
    {
        if (! isset($this->resourceModelInstance) && $this->resource_model) {
            $class = $this->getActualClassNameForMorph($this->resource_model);
            $this->resourceModelInstance = new $class;

            if (! $this->resourceModelInstance instanceof UploadImageInterface) {
                throw new \Exception('Model '.$class.' is not an instance of '.UploadImageInterface::class);
            }
        }

        return $this->resourceModelInstance;
    }

    /**
     * @param UploadImageInterface|AdminModel $resourceModelInstance
     */
    public function setResourceModel(AdminModel $resourceModelInstance)
    {
        $this->resourceModelInstance = $resourceModelInstance;
    }

    /**
     * @return string
     */
    public function getSourceImagePath()
    {
        return $this->getThumbnailPath().$this->original_image;
    }

    /**
     * @param string $thumbnailType
     * @return string
     */
    public function getRetinaImagePath($thumbnailType = 'original')
    {
        $pathInfo = pathinfo($this->getImageBaseName());
        $filename = $pathInfo['filename'].'@'.$this->retina_factor.'x.'.$pathInfo['extension'];

        return $this->getThumbnailPath($thumbnailType).$filename;
    }

    /**
     * @param string $thumbnailType
     * @return string
     */
    public function getOriginalImagePath($thumbnailType = 'original')
    {
        return $this->getThumbnailPath($thumbnailType).$this->getImageBaseName();
    }

    /**
     * @return mixed
     */
    public function getImageBaseName()
    {
        if (! isset($this->imageBaseName)) {
            $this->imageBaseName = str_replace('_source', '', $this->original_image);
        }

        return $this->imageBaseName;
    }

    /**
     * @param string $thumbnailType
     * @return string
     */
    public function getThumbnailPath($thumbnailType = 'original')
    {
        if (! isset($this->thumbnailPaths[$thumbnailType])) {
            $this->thumbnailPaths[$thumbnailType] = $this->getCurrentUploadDir().$thumbnailType.DIRECTORY_SEPARATOR;
        }

        return $this->thumbnailPaths[$thumbnailType];
    }

    /**
     * @return array|mixed|string
     * @throws \Exception
     */
    public function getCurrentUploadDir()
    {

        if (! $this->resource_model) {
            throw new \Exception('Missing resource model for model '.$this->getKey());
        }
        if (! isset($this->currentUploadDir)) {
            $className = $this->getActualClassNameForMorph($this->resource_model);
            $modelDir = (new $className())->getIdentifier();

            $this->currentUploadDir = $this->uploadDir.DIRECTORY_SEPARATOR.$modelDir.
                DIRECTORY_SEPARATOR.$this->resource_id.DIRECTORY_SEPARATOR;
        }

        return $this->currentUploadDir;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);

        if ($key == 'meta') {
            $this->attachMetaAttributes([$key => $value]);
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @param bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attachMetaAttributes($attributes);
        parent::setRawAttributes($attributes, $sync);


        return $this;
    }

    /**
     * @param array $attributes
     * @throws \Exception
     */
    protected function attachMetaAttributes(array $attributes)
    {
        if (array_key_exists('meta', $attributes)) {

            // add meta attributes.
            if (is_string($attributes['meta'])) {
                $attributes['meta'] = json_decode($attributes['meta'], true);
            }

            if (! is_array($attributes['meta']) && is_null($attributes['meta'])) {
                $attributes['meta'] = [];
            }

            // Check if fields don't intersect with main model.
            $this->checkMetaFieldCollision(array_keys($attributes['meta']));
            foreach ($attributes['meta'] as $key => $attribute) {
                $this->meta[$key] = $attribute;
            }
        }
    }

    /**
     * @param array $fields
     * @return bool
     * @throws ImageFieldCollisionException
     */
    public function checkMetaFieldCollision(array $fields)
    {
        // Do not check for fields that are excluded for collision.
        $fieldsToCheck = array_diff($fields, $this->imageAttributeFields);

        if ($intersect = array_intersect($this->getDBColumns(), $fieldsToCheck)) {
            throw new ImageFieldCollisionException('Image metadata field/s ('.implode(', ', $intersect).
                ') intersects with main model');
        }

        return false;
    }

    /**
     * @return array|mixed
     */
    public function getDBColumns()
    {
        if (! isset($this->dbColumns)) {
            $this->dbColumns = \Cache::remember($this->cacheKey.'_db_columns', 10080, function () {
                return \Schema::getColumnListing($this->getTable());
            });
        }

        return $this->dbColumns;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getMeta($key)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * Get resource model identifier.
     * Used to bridge calls to the resource model method.
     */
    public function getIdentifier()
    {
        $this->getResourceModel()->getIdentifier();
    }

    /**
     * @return array
     */
    public function getImageAttributeFields()
    {
        return $this->imageAttributeFields;
    }

    /**
     * @param $thumb
     * @return string
     * @throws \Exception
     */
    public function toHtml($thumb, $attributes = [])
    {
        $htmlAttributes = '';
 +      foreach ($attributes as $key => $attribute) {
 +            $htmlAttributes .= $key.'="'.$attribute.'" ';
 +      }
        
        if ($this->exists) {
            if (! in_array($thumb, $this->getThumbnails())) {
                throw new \Exception('Thumbnail '.$thumb.' not defined');
            }

            return view('ignicms::image.default', ['image' => $this, 'thumb' => $thumb, 'attributes' => $htmlAttributes])->render();
        }
    }

    /**
     * Override getter so we can fetch metadata from properties.
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        // Get identifier for the resource model.
        //        if ($key == 'identifier' && is_null($value)) {
        //            $resourceModel = $this->getResourceModel();
        //            if ($resourceModel && $resourceModel instanceof AdminModel) {
        //                return $resourceModel->getIdentifier();
        //            }
        //        }

        // If we don't have a value try to find it in metadata
        if (is_null($value) && $key != 'meta') {
            if (isset($this->meta[$key])) {
                $value = $this->meta[$key];
            }
        }

        return $value;
    }

    /**
     * Override magic isset so we can check for identifier metadata and properties.
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        $result = parent::__isset($key);
        if ($result) {
            return $result;
        }
        //        if (! $result && $key == 'identifier') {
        //            $resourceModel = $this->getResourceModel();
        //            if ($resourceModel && $resourceModel instanceof AdminModel) {
        //                return ! is_null($resourceModel->getIdentifier());
        //            }
        //        }

        // If we don't have a value try to find it in metadata
        if (! $result && $key != 'meta') {
            return isset($this->meta[$key]);
        }
    }
}
