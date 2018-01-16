<?php

namespace Usedesk\EmailIntegration;

// use Usedesk\EmailIntegration\Commands\Name;
use Usedesk\EmailIntegration\EventHandler;

use DateTime;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use \Webklex\IMAP\Providers\LaravelServiceProvider;

class EmailIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.(any routes, event listeners, or any other functionality you want to add to your package. have to be done before all other code is executed.)
     *
     * @return void
     */
    public function boot()
    {
        // parent::__construct($app);

        require_once __DIR__.'/../vendor/autoload.php';

        $this->setupRoutes($this->app->router);
        // $this->setupLang();
        // $this->setupConfig('integration');
        // $this->setupMigration('CreateEmailChannelTable', 'create_emailChannel_table', 1);
        // $this->setupCommands('commandName','usedesk:name');
        // $this->setupEvents('commandName','usedesk:name');
    }

     /**
     * setup Lang 
     *
     * @return void
     */
    public function setupLang()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'lang');
        $this->publishes([ __DIR__.'/../lang' => $this->resource_path('lang/vendor/usedesk'),]);
    }

     /**
     * Return a fully qualified path to a given file.
     *
     * @param string $path
     *
     * @return string
     */
    public function resource_path($path = '')
    {
        return app()->basePath().'/resources'.($path ? '/'.$path : $path);
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
            require __DIR__.'/Http/routes/routes.php';
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
        $this->publishes([ __DIR__."/../config/{$configName}.php" => config_path($configName.".php"),], 'config');
    }

    // protected function setupConfig(Application $app)
    // {
    //     $source = __DIR__.'/config/telegram.php';
    //     if ($app instanceof LaravelApplication && $app->runningInConsole()) {
    //         $this->publishes([$source => config_path('telegram.php')]);
    //     } elseif ($app instanceof LumenApplication) {
    //         $app->configure('telegram');
    //     }
    //     $this->mergeConfigFrom($source, 'telegram');
    // }
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
         if (! class_exists($className)) {//'CreateNameTables'
            // $timestamp = date('Y_m_d_His', time());
            $timestamp = (new DateTime())->format('Y_m_d_His').$timestampSuffix;

            // $this->publishes([
            //     __DIR__.'/../database/migrations/create_name_tables.php.stub' => $this->app->databasePath()."/migrations/{$timestamp}_create_name_tables.php",
            // ], 'migrations');

            $this->publishes([
                __DIR__."/../database/migrations/{$fileName}.php.stub" => database_path('migrations/'.$timestamp."_{$fileName}.php"),
            ], 'migrations');
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
            // $this->app->bind($callName, Commands\{$commandName}::class);
            // $this->commands($callName);
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
    }

    /**
     * Registers middleware
     * @return void
     */
    protected function setupMiddleware()
    {
        $this->app['router']->middleware('CanViewCRM', 'Rubenwouters\CrmLauncher\Middleware\CanViewCRM');
    }
    /**
     * Register any package services.bind any classes or functionality into the app container
     *
     * @return void
     */
    public function register()
    {
        // $this->app->register(LaravelServiceProvider::class);
        
        $this->app->make('Usedesk\EmailIntegration\Http\Controllers\IntegrationController');
        
        // $this->app->singleton(Name::class, function () {
        //     $service = new Mailchimp(config('name.apiKey'));

        //     $service->field = config('name.field', true);

        //     $configuredLists = NameListCollection::createFromConfig(config('name'));

        //     return new Name($service, $configuredLists);
        // });

        // $this->app->alias(Name::class, 'name');
    }


    /**
     * Register cron jobs
     * @return void
     */
    protected function registerCrons()
    {
        $this->app->singleton('rubenwouters.crm-launcher.src.console.kernel', function($app) {
            $dispatcher = $app->make(\Illuminate\Contracts\Events\Dispatcher::class);
            return new \Rubenwouters\CrmLauncher\Console\Kernel($app, $dispatcher);
        });
        $this->app->make('rubenwouters.crm-launcher.src.console.kernel');
    }
}