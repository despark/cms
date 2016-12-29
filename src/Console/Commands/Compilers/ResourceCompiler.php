<?php

namespace Despark\Cms\Console\Commands\Compilers;

use Illuminate\Console\Command;
use Despark\Cms\Admin\Traits\AdminFile;
use Despark\Cms\Admin\Traits\AdminImage;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Despark\Cms\Admin\Interfaces\UploadFileInterface;
use Despark\Cms\Admin\Interfaces\UploadImageInterface;
use Despark\Cms\Console\Commands\Admin\ResourceCommand;

/**
 * Class ResourceCompiler.
 */
class ResourceCompiler
{
    use AppNamespaceDetectorTrait;

    /**
     * @var Command|ResourceCommand
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
        ':implementations' => [],
        ':uses' => [],
        ':traits' => [],
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
     *
     * @todo why setting options where we can get it from command? Either remove command or keep options
     */
    public function __construct(Command $command, $identifier, $options)
    {
        $this->command = $command;
        $this->identifier = $identifier;
        $this->options = $options;
    }

    /**
     * @param $template
     *
     * @return string
     *
     * @throws \Exception
     */
    public function render_model($template)
    {
        if ($this->options['image_uploads'] || $this->options['file_uploads']) {
            if ($this->options['image_uploads']) {
                $this->modelReplacements[':uses'][] = UploadImageInterface::class;
                $this->modelReplacements[':implementations'][] = class_basename(UploadImageInterface::class);
                $this->modelReplacements[':uses'][] = AdminImage::class;
                $this->modelReplacements[':traits'][] = class_basename(AdminImage::class);
            }

            if ($this->options['file_uploads']) {
                $this->modelReplacements[':uses'][] = UploadFileInterface::class;
                $this->modelReplacements[':implementations'][] = class_basename(UploadFileInterface::class);
                $this->modelReplacements[':uses'][] = AdminFile::class;
                $this->modelReplacements[':traits'][] = class_basename(AdminFile::class);
            }
        }

        $this->modelReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->modelReplacements[':table_name'] = str_plural($this->identifier);
        $this->modelReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->modelReplacements[':identifier'] = $this->identifier;

        $this->prepareReplacements();

        // Check to see if route is not already used
        if (\Route::has($this->identifier.'.index')) {
            // Check if admin is also free
            if (\Route::has('admin.'.$this->identifier.'.index')) {
                throw new \Exception('Resource `'.$this->identifier.'` already exists');
            }

            // We need to append admin
            foreach ($this->routeActions as $action) {
                $this->routeNames[$action] = 'admin.'.$this->identifier.'.'.$action;
            }
        }

        $route = "Route::resource('$this->identifier', 'Admin\\".$this->command->controller_name($this->identifier)."'";
        if (! empty($this->routeNames)) {
            // create the resource names
            $route .= ',['.PHP_EOL."'names' => [".PHP_EOL;
            foreach ($this->routeNames as $action => $name) {
                $route .= "'$action' => '$name',".PHP_EOL;
            }

            $route .= ']'.PHP_EOL.']);'.PHP_EOL;
        } else {
            // Close the Route resource
            $route .= ');'.PHP_EOL;
        }

        if ($this->options['file_uploads']) {
            $route .= "Route::get('$this->identifier/delete/{fileFieldName}', 'Admin\\".$this->command->controller_name($this->identifier)."@deleteFile');".PHP_EOL;
        }

        $this->appendToFile(base_path('routes/resources.php'), $route);

        $template = strtr($template, $this->modelReplacements);

        return $template;
    }

    /**
     * Prepare Replacements.
     */
    private function prepareReplacements()
    {
        $usesString = '';
        foreach ($this->modelReplacements[':uses'] as $use) {
            $usesString .= 'use '.$use.';'.PHP_EOL;
        }
        $this->modelReplacements[':uses'] = $usesString;

        $this->modelReplacements[':implementations'] = ! empty($this->modelReplacements[':implementations']) ?
            'implements '.implode(', ', $this->modelReplacements[':implementations']) : '';

        $this->modelReplacements[':traits'] = ! empty($this->modelReplacements[':traits']) ?
            'use '.implode(', ', $this->modelReplacements[':traits']).';' : '';
    }

    /**
     * @param $template
     *
     * @return string
     */
    public function render_config($template)
    {
        if ($this->options['image_uploads']) {
            $this->configReplacements[':image_fields'] = "'image_fields' => [
        'image' => [
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
     *
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
     *
     * @return string
     */
    public function render_controller($template)
    {
        $this->controllerReplacements[':app_namespace'] = $this->getAppNamespace();
        $this->controllerReplacements[':resource'] = $this->identifier;
        $this->controllerReplacements[':model_name'] = $this->command->model_name($this->identifier);
        $this->controllerReplacements[':request_name'] = $this->command->request_name($this->identifier);
        $this->controllerReplacements[':controller_name'] = $this->command->controller_name($this->identifier);
        $this->controllerReplacements[':identifier'] = $this->identifier;

        $routeName = empty($this->routeNames) ? $this->identifier : 'admin.'.$this->identifier;

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
     *
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
     *
     * @throws \Exception
     */
    public function appendToFile($file, $content)
    {
        if (! file_exists($file)) {
            throw new \Exception('File is missing');
        }
        file_put_contents($file, $content, FILE_APPEND);
    }
}
