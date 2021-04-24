#!/usr/bin/env php
<?php

use BHayes\CLI\CLI;

require_once 'vendor/autoload.php';

$function = function (int $number) {
    return __METHOD__ . " was executed! Your number was $number";
};

$cli = new CLI($function);
$cli->run();
