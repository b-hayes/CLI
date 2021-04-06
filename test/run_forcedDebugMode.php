#!/usr/bin/env php
<?php
require_once __DIR__ . '/_environment.php';

( new \BHayes\CLI\CLI(new class () {
    public function helloWorld()
    {
        echo __METHOD__, " was executed!";
    }
}))->run(true);
