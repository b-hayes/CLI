#!/usr/bin/env php
<?php

require_once __DIR__ . '/_environment.php';

( new \BHayes\CLI\CLI(new \BHayes\CLI\Test\TestSubject()) )->run();
