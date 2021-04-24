#!/usr/bin/env php
<?php

use BHayes\CLI\CLI;

require_once __DIR__ . '/_environment.php';

$class = new class {
    public function __invoke()
    {
        echo __METHOD__, " was executed!";
    }
};

$cli =  new CLI($class);
$cli->run();
