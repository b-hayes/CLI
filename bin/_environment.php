<?php
/*
 * Environment wrapper for autoload
 *
 * Anything run from command line should require this file instead of the usual autoload reference.
 *
 * Remember that the autoloader will be in a different location when this package is installed by a project.
 *
 *  cli often shows errors twice if you have both of them on because log goes to stdout
 *  so you only want one of them on.
 *
 * In dev testing should display errors not log them.
 * It may or may not be better to use logs instead for published cli tools as the user might have
 * some special setup for logging error in command line however,
 * i think its always better to see an error than diagnosing for problems when nothing is output.
 */
ini_set('log_errors', 0);
ini_set('display_errors', 1);

//Check the PHP version at run time because user might be swapping php versions at will (i do it all the time).
if (phpversion() < 7.2) {
    die('This package only works with php 7.2 or higher.');
}

//check two locations for autoloader as it will be different when installed as a package.
$realpath = realpath(__DIR__ . '/../vendor/autoload.php')//the normal path during development
    ?: realpath(__DIR__ . '/../../../autoload.php'); //the path if installed by a parent project
//Note: the reason for not just using the current working directory is so that we dont load an incompatible set of dependencies
// if this package is run as a global command inside other projects.

if (!$realpath) {
    echo "can not find autoload.php, you might need to run composer install.\n";
    exit(1);
}
require_once $realpath;

//remove any globalised variables you created in the process of setting up your environment
unset($realpath);
