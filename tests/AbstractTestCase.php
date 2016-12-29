<?php

namespace Despark\Tests\Cms;

use ReflectionClass;
use GrahamCampbell\TestBench\AbstractPackageTestCase;

/**
 * Class AbstractTestCase.
 */
abstract class AbstractTestCase extends AbstractPackageTestCase
{
    /**
     * @var
     */
    protected $migrationPath;

    public function setUp()
    {
        parent::setUp();
        $this->migrationPath = realpath(__DIR__.'/migrations');
        $this->withFactories(__DIR__.'/../database/factories');
    }

    /**
     * Get the service provider class.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return string
     */
    protected function getServiceProviderClass($app)
    {
        return \Despark\Cms\Providers\AdminServiceProvider::class;
    }

    /**
     * Sets a protected property on a given object via reflection.
     *
     * @param $object - instance in which protected value is being modified
     * @param $property - property on instance being modified
     * @param $value - new value of the property being modified
     *
     * @return void
     */
    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }

    /*
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    //    protected function getEnvironmentSetUp($app)
    //    {
    //
    //       parent::getEnvironmentSetUp($)
    //    }
}
