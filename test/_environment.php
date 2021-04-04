<?php
/*
 * Environment wrapper for DEV testing.
 * Anything run from command line should require this file instead of the usual autoload reference.
 *
 * Checks 2 autoloader locations:
 *  Autoloader will be in a different location when this package is installed by a project.
 *
 * Modifies php error reporting:
 *  php via cli often prints errors twice by default because both log_errors and display_errors
 *  are directed to stdout. And we only want to see error once.
 *
 */
ini_set('log_errors', 0);
ini_set('display_errors', 1);

//Check the PHP version at run time because user might be swapping php versions at will
if (phpversion() < 7.2) {
    die('This package only works with php 7.1 or higher');
}

//check two locations for autoloader as it will be different when installed.
$realpath = realpath(__DIR__ . '/../vendor/autoload.php')//the normal path during development
    ?: realpath(__DIR__ . '/../../../autoload.php'); //the path if installed by a parent project
require_once $realpath;

//remove any globalised variables you created in the process of setting up your environment
unset($realpath);
