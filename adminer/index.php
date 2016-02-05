<?php

ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) . '/');

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '../app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '../vendor');

// absolute filesystem path to the temporary files
define('TEMP_DIR', APP_DIR . '/temp');

// absolute filesystem path to the temporary files
define('LOG_DIR', APP_DIR . '/log');

require LIBS_DIR . '/nette.min.php';
require LIBS_DIR . '/shortcuts.php';

// Configure application
$configurator = new NConfigurator;
$configurator->setProductionMode(false);

$configurator->enableDebugger(LOG_DIR);

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(TEMP_DIR);

$configurator->createRobotLoader()
    ->addDirectory(LIBS_DIR)
    ->addDirectory(APP_DIR)
    ->register();

// Create Dependency Injection container from config.neon file
//$configurator->addConfig(APP_DIR . '/config/config.neon');
$configurator->addConfig(APP_DIR . '/config/config.db.neon');

$container = $configurator->createContainer();

function adminer_object()
{

    class AdminerSoftware extends Adminer
    {

        private $container;
        private $user;

        function __construct(SystemContainer $container)
        {
            $this->container = $container;
            $this->user = $container->getService('user');
            $this->parameters = $container->parameters;
        }

        function name()
        {
            // custom name in title and heading
            return 'DoNaTio';
        }

        function credentials()
        {
            // server, username and password for connecting to database
            $c = $this->parameters['database'];

            return array('mariadb55.websupport.sk:3310', $c['username'], $c['password']);
        }

        function database()
        {
            // database name, will be escaped by Adminer
            return $this->parameters['database']['database'];
        }

        function login()
        {
            return $this->isLoggedIn() && $this->isInRole();
        }


        function loginForm()
        {
            if (!$this->isLoggedIn()) {
                echo "<p>Prihlaste se prosím ke svému účtu přes tradiční formulář.</p>";
            } else if (!$this->isInRole()) {
                echo "<p>Váš účet nemá oprávnění k Adminer Editor.</p>";
            }
        }

        private function isLoggedIn()
        {
            return $this->user->isLoggedIn();
        }

        private function isInRole()
        {
            return $this->user->isInRole('manage_cms');
        }

    }

    global $container;
    return new AdminerSoftware($container);
}

include "./adminer-4.1.0.php";
