<?php
define('TESTING', true);//
error_reporting(-1);

date_default_timezone_set('UTC');

$filename = __DIR__ .'/../../../autoload.php';

if (!file_exists($filename)) {
    echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~" . PHP_EOL;
    echo " You need to execute `composer install` before running the tests. " . PHP_EOL;
    echo "         Vendors are required for complete test execution.        " . PHP_EOL;
    echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~" . PHP_EOL . PHP_EOL;
    include __DIR__ .'/../autoload.php';
} else {
	$loader = require $filename;
	$loader->add('\\x51\\tests\\frontend', __DIR__);
}

