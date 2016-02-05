<?php
function set_magic_quotes_runtime(){
    return false;
}

require LIBS_DIR . '/nette.min.php';
require LIBS_DIR . '/SQLiteStorage.php';

$loader = new NRobotLoader ();
$loader->setCacheStorage(new SQLiteStorage());
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
$loader->addDirectory(WWW_DIR . '/require_modules');
$loader->addDirectory(WWW_DIR . '/classes');
$loader->register();

$config = NEnvironment::loadConfig(APP_DIR . '/config/config.neon');