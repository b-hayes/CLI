#!/usr/bin/env php
<?php

use BHayes\CLI\CLI;

require_once 'vendor/autoload.php';

$class = new class {
    public function __invoke(int $number)
    {
        echo __METHOD__ . " was executed! Your number was $number";
    }

    public function youCantRunThis()
    {
        echo __METHOD__ . " was executed!";
    }
};

$cli = new CLI($class);
$cli->run();
