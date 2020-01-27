#!/usr/bin/env php
<?php
require_once __DIR__.'/_environment.php';
chdir(__DIR__);
//echo __FILE__;

//warning: these tests will only work on a unix system because you cant just execute any file in windows
// windows = '.' dot is not a command
// mingwin = file opens in notepad.

//running with no params should show available functions
assert(stripos(`./run_TestSubject.php`,'functions available') !== false);

//[ can execute ]
//functions can be run by name
assert(stripos(`./run_TestSubject.php simple`,'TestSubject::simple was executed') !== false);
//function name is case insensitive
assert(stripos(`./run_TestSubject.php SiMplE`,'TestSubject::simple was executed') !== false);

//[ should fail ]
//should not be able to pass additional arguments to functions
assert(stripos(`./run_TestSubject.php simple with extra params should cause an error`,'Too many arguments.') !== false);
assert(stripos(`./run_TestSubject.php simple with extra params should not be run`,'TestSubject::simple was executed') === false);
