<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Usamamuneerchaudhary\Commentify\Providers\CommentifyServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            CommentifyServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        \Route::get('login', function () {
            return 'login page';
        })->name('login');
        $this->app->register(\Flux\FluxServiceProvider::class);
        Model::unguard();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__ . '/../database/migrations')
        ]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        // Clean up database tables
        Schema::dropIfExists('comment_reports');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('comments');

        // Restore error and exception handlers to prevent risky test warnings
        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    /**
     * @param $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);


        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('articles', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('episodes', function ($table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

}
