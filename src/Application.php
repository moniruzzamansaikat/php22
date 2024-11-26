<?php

namespace Php22;

class Application
{
    private $container;

    public function __construct()
    {
        $this->container = Container::getInstance();

        // Register core services
        $this->registerServices();
    }

    /**
     * Run the application.
     */
    public function run()
    {
        $this->loadRoutes();
        $this->container->resolve('router')->dispatch();
    }

    /**
     * Register core services into the container.
     */
    private function registerServices()
    {
        $this->container->bind('router', function () {
            return new Router();
        });

        $this->container->bind('db', function () {
            return new \Php22\Db\Database();
        });

        $this->container->bind('config', function () {
            return require base_path('config/framework.php');
        });

        $this->container->bind('templateEngine', function () {
            $config = $this->container->resolve('config');
            return new TemplateEngine($config['views_path'], $config['cache_path']);
        });
    }

    /**
     * Load application routes.
     */
    private function loadRoutes()
    {
        require_once base_path('routes/web.php');
    }
}
