<?php

namespace Despark\Cms\Console\Commands;

use Illuminate\Console\Command;
use Despark\Cms\Console\Commands\Compilers\ResourceCompiler;
use Symfony\Component\Console\Input\InputArgument;
use File;

class AdminResourceCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'admin:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create necessary files for CMS resource.';

    protected $identifier;

    protected $compiler;

    protected $resourceOptions = [
        'image_uploads' => false,
        'file_uploads' => false,
        'migration' => false,
        'create' => false,
        'edit' => false,
        'destroy' => false,
    ];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the command.
     */
    public function fire()
    {
        $this->identifier = self::normalize($this->argument('identifier'));

        $this->askImageUploads();
        $this->askFileUploads();
        $this->askMigration();
        $this->askActions();

        $this->compiler = new ResourceCompiler($this, $this->identifier, $this->resourceOptions);

        $this->createResource('config');
        $this->createResource('model');
        $this->createResource('request');
        $this->createResource('controller');

        if ($this->resourceOptions['migration']) {
            $this->createResource('migration');
        }
    }

    protected function createResource($type)
    {
        $template = $this->getTemplate($type);
        $template = $this->compiler->{'render_'.$type}($template);
        $path = config('admin.bootstrap.paths.'.$type);
        $filename = $this->{$type.'_name'}().'.php';
        $this->saveResult($template, $path, $filename);
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public static function normalize($str)
    {
        return snake_case($str);
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_".strtolower($c[1]);');

        $snake = preg_replace_callback('/([A-Z])/', $func, $str);

        return str_replace(' ', '', $snake);
    }

    protected function askImageUploads()
    {
        $answer = $this->confirm('Do you need image uploads?');

        $this->resourceOptions['image_uploads'] = $answer;
    }

    protected function askFileUploads()
    {
        $answer = $this->confirm('Do you need file uploads?');

        $this->resourceOptions['file_uploads'] = $answer;
    }

    protected function askMigration()
    {
        $answer = $this->confirm('Do you need migration?');

        $this->resourceOptions['migration'] = $answer;
    }

    protected function askActions()
    {
        $answer = $this->ask('Which actions do you need? [create, edit, destroy]', 'none');
        $answer = str_replace(' ', '', $answer);

        $actions = explode(',', $answer);
        $actions = array_map('strtolower', $actions);
        if (in_array('create', $actions)) {
            $this->resourceOptions['create'] = true;
        }

        if (in_array('edit', $actions)) {
            $this->resourceOptions['edit'] = true;
        }

        if (in_array('destroy', $actions)) {
            $this->resourceOptions['destroy'] = true;
        }
    }

    public function getTemplate($type)
    {
        return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.$type.'.stub');
    }

    protected function saveResult($template, $path, $filename)
    {
        $file = $path.DIRECTORY_SEPARATOR.$filename;
        if (File::exists($file)) {
            $result = $this->confirm('File "'.$filename.'" already exist. Overwrite?', false);
            if ( ! $result) {
                return;
            }
        }

        File::put($file, $template);
        $this->info('File "'.$filename.'" was created.');
    }

    /**
     * @return string
     * @todo this is not needed in the command we should move it into the compiler
     */
    public function model_name()
    {
        return studly_case($this->identifier);
    }

    public function config_name()
    {
        return $this->identifier;
    }

    public function request_name()
    {
        return str_plural(studly_case($this->identifier)).'Request';
    }

    public function controller_name()
    {
        return str_plural(studly_case($this->identifier)).'Controller';
    }

    public function migration_name()
    {
        return date('Y_m_d_His').'_create_'.str_plural($this->identifier).'_table';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'identifier',
                InputArgument::REQUIRED,
                'Identifier',
            ],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [

        ];
    }
}
