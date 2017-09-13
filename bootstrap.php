<?php

ini_set('error_log', __DIR__."\my_error_log.txt");
error_reporting(-1);

// Linux and Windows operating systems
define('DS', DIRECTORY_SEPARATOR);

define('DEBUG', true);
//define('DEBUG', false);
define('ROOT', dirname(dirname(__FILE__)));

require 'vendor/autoload.php';
