<?php

namespace Usedesk\VkIntegration;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Bus\DispatchesJobs;

use DateTime;

use Usedesk\EmailIntegration\EventHandler;
use Usedesk\EmailIntegration\Jobs\TriggerRequest;

class VkIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
         require_once __DIR__.'/../vendor/autoload.php';

        $this->setupLang();
        $this->setupRoutes($this->app->router);
        $this->setupConfig('integration');
        $this->setupMigration('CreateEmailChannelTable', 'create_emailChannel_table', 1);
        $this->setupCommands('commandName','usedesk:name');
        $this->setupEvents('commandName','usedesk:name');
        $this->setupMiddleware('commandName','usedesk:name');
    }

     /**
     * setup Lang 
     *
     * @return void
     */
    public function setupLang()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'lang');
        // $this->publishes([ __DIR__.'/../lang' => $this->resource_path('lang/vendor/usedesk'),]);
    }

     /**
     * setup Routes 
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'Usedesk\EmailIntegration\Http\Controllers'], function($router)
        {
            require __DIR__.'/Http/routes.php';
        });
    }
    /**
     * setup Config 
     *
     * @param string $phrase Phrase to return
     * @return void
     */
    protected function setupConfig($configName)
    {
        $this->mergeConfigFrom(__DIR__."/../config/{$configName}.php", $configName);
        // $this->publishes([ __DIR__."/../config/{$configName}.php" => config_path($configName.".php"),], 'config');
    }
    /**
     * setup Migrations
     *
     * @param string $className
     * @param string $fileName
     * @param int $timestampSuffix
     * @return void
     */
    protected function setupMigrations(string $className, string $fileName, int $timestampSuffix)
    {
         if (! class_exists($className)) 
         {
            $timestamp = (new DateTime())->format('Y_m_d_His').$timestampSuffix;

            $migrationPath = __DIR__."/../database/migrations/{$fileName}.php.stub";
            
            $path = database_path('migrations/'.$timestamp."_{$fileName}.php");

            // $this->publishes([$migrationPath => $path], 'migrations');
        }
    }
    /**
     * setup Commands
     *
     * @param string $commandName
     * @param string $callName
     * @return void
     */
    protected function setupCommands(string $commandName,string $callName)
    {
        if ($this->app->runningInConsole()) 
        {
            $this->app->bind($callName, Commands\{$commandName}::class);
            $this->commands($callName);
        }
    }
    /**
     * setup Events
     *
     * @param string $commandName
     * @param string $callName
     * @return void
     */
    protected function setupEvents(string $commandName,string $callName)
    {
        $this->app['events']->subscribe(EventHandler::class);

        // foreach ($this->listeners as $eventName) {
        //     $this->app['events']->listen($eventName, [$this, 'handleEvent']);
        // }
    }
    /**
     * Registers middleware
     * @return void
     */
    protected function setupMiddleware()
    {
        $this->app['router']->middleware('social', 'Usedesk\EmailIntegration\Middlewares\Social');
    }
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->register(NameServiceProvider::class);
        // $this->app->make('Usedesk\EmailIntegration\Http\Controllers\NameController');
    }
}