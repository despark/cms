<?php

namespace Despark\Cms\Console\Commands\Compilers;

use Despark\Cms\Console\Commands\AdminResourceCommand;
use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

/**
 * Class ResourceCompiler
 * @package Despark\Cms\Console\Commands\Compilers
 */
class ResourceCompiler
{
    use AppNamespaceDetectorTrait;

    /**
     * @var Command|AdminResourceCommand
     */
    protected $command;

    /**
     * @var
     */
    protected $identifier;

    /**
     * @var
     */
    protected $options;

    /**
     * @var array
     */
    protected $modelReplacements = [
        ':identifier' => '',
        ':model_name' => '',
        ':app_namespace' => '',
        ':image_traits_include' => '',
        ':image_traits_use' => '',
        ':file_traits_include' => '',
        ':file_traits_use' => '',
        ':table_name' => '',
    ];

    /**
     * @var array
     */
    protected $configReplacements = [
        ':image_fields' => '',
        ':file_fields' => '',
    ];

    /**
     * @var array
     */
    protected $controllerReplacements = [
        ':identifier' => '',
        ':model_name' => '',
        ':controller_name' => '',
        ':app_namespace' => '',
        ':resource' => '',
        ':create_route' => '',
        ':edit_route' => '',
        ':destroy_route' => '',
    ];

    /**
     * @var array
     */
    protected $routeActions = [
        'index',
        'store',
        'create',
        'update',
        'show',
        'destroy',
        'edit',
    ];

    protected $routeNames = [];

    /**
     * @param Command $command
     * @param         $identifier
     * @param         $options
     * @todo why setting options where we can get it from command? Either remove command or keep options.
     */
    public function __construct(Command $command, $identifier, $options)
    {
        $this->command = $command;
        $this->identifier = $identifier;
        $this->options = $options;
    }

    /**
     * @param $template
     * @return string
     * @throws \Exception
     */
    public function render_model($template)
    {
        if ($this->options['image_uploads']) {
            $this->modelReplacements[':image_traits_include'] = 'use Despark\Cms\Admin\Traits\UploadImagesTrait;';
            $this->modelReplacements[':image_traits_use'] = 'use UploadImagesTrait;';
        }

        if ($this->options['file_uploads']) {
            $this->modelReplacements[':file_traits_include'] = 'use Despark\Cms\Admin\Traits\UploadFilesTrait;';
            $this->modelReplacements[':file_traits_use'] = 'use UploadFilesTrait;';
        }

        $this->modelReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->modelReplacements[':table_name'] = str_plural($this->identifier);
        $this->modelReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->modelReplacements[':identifier'] = $this->identifier;

        $identifierPlural = str_plural($this->identifier);

        // Check to see if route is not already used
        if (\Route::has($identifierPlural.'.index')) {
            // Check if admin is also free
            if (\Route::has('admin.'.$identifierPlural.'.index')) {
                throw new \Exception('Resource `'.$this->identifier.'` already exists');
            }
            // We need to append admin
            foreach ($this->routeActions as $action) {
                $this->routeNames[$action] = 'admin.'.$identifierPlural.'.'.$action;
            }

        }

        $route = "Route::resource('".$identifierPlural."', '".$this->getAppNamespace().
            'Http\Controllers\Admin\\'.$this->command->controller_name($this->identifier)."'";
        if ( ! empty($this->routeNames)) {
            // create the resource names
            $route .= ",[".PHP_EOL."'names' => [".PHP_EOL;
            foreach ($this->routeNames as $action => $name) {
                $route .= "'$action' => '$name',".PHP_EOL;
            }
            $route .= ']'.PHP_EOL.']);'.PHP_EOL;

        } else {
            // Close the Route resource
            $route .= ');'.PHP_EOL;
        }
        if ($this->options['file_uploads']) {
            $route .= "Route::get('".$identifierPlural."/delete/{fileFieldName}', '".$this->getAppNamespace().
                'Http\Controllers\Admin\\'.$this->command->controller_name($this->identifier)."@deleteFile');".PHP_EOL;
        }

        $this->appendToFile(app_path('Http/resourcesRoutes.php'), $route);

        $template = strtr($template, $this->modelReplacements);

        return $template;
    }

    /**
     * @param $template
     * @return string
     */
    public function render_config($template)
    {
        if ($this->options['image_uploads']) {
            $this->configReplacements[':image_fields'] = "'image_fields' => [
        'image'  => [
            'thumbnails' => [
                'admin' => [
                    'width' => 150,
                    'height' => null,
                    'type' => 'resize',
                ],
                'normal' => [
                    'width' => 960,
                    'height' => null,
                    'type' => 'crop',
                ],
            ],
        ],
    ],";
        }

        if ($this->options['file_uploads']) {
            $this->configReplacements[':file_fields'] = "'file_fields' => [
        'file'  => [
            'dirName' => '',
        ],
    ],";
        }

        $template = strtr($template, $this->configReplacements);

        return $template;
    }

    /**
     * @param $template
     * @return string
     */
    public function render_request($template)
    {
        $this->modelReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->modelReplacements[':request_name'] = $this->command->request_name($this->identifier);
        $this->modelReplacements[':model_name'] = $this->command->model_name($this->identifier);

        $template = strtr($template, $this->modelReplacements);

        return $template;
    }

    /**
     * @param $template
     * @return string
     */
    public function render_controller($template)
    {
        $this->controllerReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->controllerReplacements[':resource'] = str_plural($this->identifier);
        $this->controllerReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->controllerReplacements[':request_name'] = $this->command->request_name($this->identifier);
        $this->controllerReplacements[':controller_name'] = $this->command->controller_name($this->identifier);
        $this->controllerReplacements[':identifier'] = $this->identifier;

        $routeName = empty($this->routeNames) ? str_plural($this->identifier) : 'admin.'.str_plural($this->identifier);

        if ($this->options['create']) {
            $this->controllerReplacements[':create_route'] = '$this->viewData'."['createRoute'] = '".$routeName.".create';";
        }

        if ($this->options['edit']) {
            $this->controllerReplacements[':edit_route'] = '$this->viewData'."['editRoute'] = '".$routeName.".edit';";
        }

        if ($this->options['destroy']) {
            $this->controllerReplacements[':destroy_route'] = '$this->viewData'."['deleteRoute'] = '".$routeName.".destroy';";
        }

        $template = strtr($template, $this->controllerReplacements);

        return $template;
    }

    /**
     * @param $template
     * @return string
     */
    public function render_migration($template)
    {
        $this->controllerReplacements[':migration_class'] = 'Create'.str_plural(studly_case($this->identifier)).'Table';
        $this->controllerReplacements[':table_name'] = str_plural($this->identifier);

        $template = strtr($template, $this->controllerReplacements);

        return $template;
    }

    /**
     * @param $file
     * @param $content
     * @throws \Exception
     */
    public function appendToFile($file, $content)
    {
        if ( ! file_exists($file)) {
            throw new \Exception('File is missing');
        }
        file_put_contents($file, $content, FILE_APPEND);
    }
}
