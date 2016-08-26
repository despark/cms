<?php


namespace Commands;


use AbstractIgniTests;
use Despark\Cms\Console\Commands\AdminResourceCommand;
use Despark\Cms\Console\Commands\Compilers\ResourceCompiler;
use Mockery\Mock;

class ResourceTest extends AbstractIgniTests
{

    /**
     * @group commands
     * @group resource
     */
    public function testRoutesGeneration()
    {
        // Mock has property
        \Route::shouldReceive('has')
              ->andReturnUsing([$this, 'hasRoute']);

        $command = \Mockery::mock(AdminResourceCommand::class)->makePartial();

        $this->setProtectedProperty($command, 'identifier', 'test_resource');

        $resourceOptions = [
            'image_uploads' => true,
            'file_uploads' => true,
            'migration' => false,
            'create' => true,
            'edit' => true,
            'destroy' => true,
        ];

        $this->setProtectedProperty($command, 'resourceOptions', $resourceOptions);

        $template = $command->getTemplate('model');

        /** @var Mock|ResourceCompiler $compiler */
        $compiler = \Mockery::mock(ResourceCompiler::class, [$command, 'test_resource', $resourceOptions])
                            ->makePartial();

        $compiler->shouldReceive('appendToFile')->andReturnUsing([$this, 'dummyAppendToFile']);

        $template = $compiler->render_model($template);

        $this->dummyAppendToFile(null, $template);

        $output = exec('php -l '.storage_path('dummy_file.test'));

        $this->assertNotFalse(strstr($output, 'No syntax errors detected'));

        // Check routes
        $output = exec('php -l '.storage_path('dummy_file.test'));


        $this->assertNotFalse(strstr($output, 'No syntax errors detected'));

        // We need a way to undo changes.
    }

    public function dummyAppendToFile($file = null, $content)
    {
        $file = storage_path('dummy_file.test');
        \File::put($file, $content);
    }

    public function hasRoute($routeName)
    {
        return (strstr($routeName, 'admin.') !== false) ? false : true;
    }

}