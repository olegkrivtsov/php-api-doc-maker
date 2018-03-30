<?php
require __DIR__ . '/vendor/autoload.php';

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(__DIR__);

// This is needed by PHP parser because it can reach the default nesting level of 256 and
// cause error.
ini_set('xdebug.max_nesting_level', 3000);

$app = new PhpApiDocMaker\Application();
return $app->run($argc, $argv);
