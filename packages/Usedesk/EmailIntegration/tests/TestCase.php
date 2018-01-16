<?php

namespace Usedesk\EmailIntegration\Test\Integration;

use Usedesk\EmailIntegration\Test\TestHelper;
use Usedesk\EmailIntegration\EmailIntegrationServiceProvider;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;

use Orchestra\Testbench\TestCase as Orchestra;
// use PHPUnit\Framework\TestCase;

abstract class TestCase extends Orchestra
{
    /** @var \Spatie\Backup\Test\TestHelper */

    protected $testHelper;

    public function setUp()
    {
        parent::setUp();

        $this->checkRequirements();

        $this->getPackageProviders($this->app);

        $this->getEnvironmentSetUp($this->app);

        $this->setUpDatabase();

        // $this = new TestHelper();
        // $this->app['config']->set('app.url', 'https://mysite.com');
        // $this->app['router']->get('/')->middleware(CaptureReferer::class, function () {
        //     return response(null, 200);
        // });
        // $this->session = $this->app['session.store'];
        // $this->referer = $this->app['referer'];
    }

    protected function checkRequirements()
    {
        parent::checkRequirements();

        collect($this->getAnnotations())->filter(function ($location) {
            return in_array('!Travis', array_get($location, 'requires', []));
        })->each(function ($location) {
            getenv('TRAVIS') && $this->markTestSkipped('Travis will not run this test.');
        });
    }
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            EmailIntegrationServiceProvider::class,
        ];
    }
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'log');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
        
        $app['config']->set('app.key', 'base64:lDKn1i84qbUj14WIHQmoe3MMcInE8wRTaYKlM/PJVF4=');
    }

    protected function setUpDatabase()
    {
        $this->initializeTempDirectory();

        $this->resetDatabase();
        
        $this->createTables('articles', 'users');

        $this->seedModels(Article::class, User::class);
    }

    protected function getTempDirectory(): string
    {
        return __DIR__.'/temp';
    }

    protected function initializeTempDirectory()
    {
        touch($this->getTempDirectory().'/database.sqlite');
    }

    protected function resetDatabase()
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);
    }
    /**
     * @param array $tableName
     */
    protected function createTables(...$tableNames)
    {
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        collect($tableNames)->each(function (string $tableName) 
        {
            $this->app['db']->connection()->getSchemaBuilder()->create($tableName, function (Blueprint $table) use ($tableName) 
            {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('text')->nullable();
                $table->timestamps();
                $table->softDeletes();

                if ($tableName === 'articles') {
                    $table->integer('user_id')->unsigned()->nullable();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('json')->nullable();
                }
            });
        });
    }
    /**
     * @param array $modelClasses
     */
    protected function seedModels(...$modelClasses)
    {
        // $this->withFactories(__DIR__.'/factories');
        
        collect($modelClasses)->each(function (string $modelClass) {
            foreach (range(1, 0) as $index) {
                $modelClass::create(['name' => "name {$index}"]);
            }
        });
    }

    // protected function withConfig(array $config)
    // {
    //     $this->app['config']->set($config);
    //     $this->app->forgetInstance(Referer::class);
    //     $this->referer = $this->app->make(Referer::class);
    // }
}