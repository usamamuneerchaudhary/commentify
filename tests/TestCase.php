<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Usamamuneerchaudhary\Commentify\Providers\CommentifyServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param $app
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [CommentifyServiceProvider::class];
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Model::unguard();
        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/../migrations')
        ]);
    }


    /**
     * @param $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);
    }

}
