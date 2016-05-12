<?php

namespace Despark\Console\Commands\Compilers;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class ResourceCompiler
{
    use AppNamespaceDetectorTrait;

    protected $command;

    protected $identifier;

    protected $options;

    protected $modelReplacements = [
        ':identifier' => '',
        ':model_name' => '',
        ':app_namespace' => '',
        ':traits_include' => '',
        ':traits_use' => '',
        ':table_name' => '',
    ];

    protected $configReplacements = [
        ':image_fields' => '',
    ];

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
     * @param Command $command
     * @param string  $modelClass
     * @param string  $title
     */
    public function __construct(Command $command, $identifier, $options)
    {
        $this->command = $command;
        $this->identifier = $identifier;
        $this->options = $options;
    }

    public function renderModel($template)
    {
        if ($this->options['image_uploads']) {
            $this->modelReplacements[':traits_include'] = 'use Despark\Admin\Traits\AdminImage;';
            $this->modelReplacements[':traits_use'] = 'use AdminImage;';
        }

        $this->modelReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->modelReplacements[':table_name'] = str_plural($this->identifier);
        $this->modelReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->modelReplacements[':identifier'] = $this->identifier;

        $template = strtr($template, $this->modelReplacements);

        return $template;
    }

    public function renderConfig($template)
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

        $template = strtr($template, $this->configReplacements);

        return $template;
    }

    public function renderController($template)
    {
        $this->controllerReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->controllerReplacements[':resource'] = str_plural($this->identifier);
        $this->controllerReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->controllerReplacements[':controller_name'] = $this->command->controller_name($this->identifier);
        $this->controllerReplacements[':identifier'] = $this->identifier;
        if ($this->options['create']) {
            $this->controllerReplacements[':create_route'] = '$this->viewData'."['createRoute'] = 'admin.".str_plural($this->identifier).".create';";
        }

        if ($this->options['edit']) {
            $this->controllerReplacements[':edit_route'] = '$this->viewData'."['editRoute'] = 'admin.".str_plural($this->identifier).".edit';";
        }

        if ($this->options['destroy']) {
            $this->controllerReplacements[':destroy_route'] = '$this->viewData'."['destroyRoute'] = 'admin.".str_plural($this->identifier).".destroy';";
        }

        $template = strtr($template, $this->controllerReplacements);

        return $template;
    }

    public function renderMigration($template)
    {
        $this->controllerReplacements[':migration_class'] = 'Create'.str_plural(studly_case($this->identifier)).'Table';
        $this->controllerReplacements[':table_name'] = str_plural($this->identifier);

        $template = strtr($template, $this->controllerReplacements);

        return $template;
    }
}
