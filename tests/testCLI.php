#!/usr/bin/env php
<?php
require_once __DIR__.'/_environment.php';
chdir(__DIR__);

//running with no params should show available functions
assert(stripos(`php run_TestSubject.php`,'functions available') !== false);

//[ can execute ]
//functions can be run by name
assert(stripos(`php run_TestSubject.php simple`,'TestSubject::simple was executed') !== false);
//function name is case insensitive
assert(stripos(`php run_TestSubject.php SiMplE`,'TestSubject::simple was executed') !== false);

//[ should fail ]
//should not be able to pass additional arguments to functions
assert(stripos(`php run_TestSubject.php simple with extra params should cause an error`,'Too many arguments.') !== false);
assert(stripos(`php run_TestSubject.php simple with extra params should not be run`,'TestSubject::simple was executed') === false);
