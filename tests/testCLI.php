#!/usr/bin/env php
<?php
require_once __DIR__.'/_environment.php';
chdir(__DIR__);
//echo __FILE__;

//warning: these tests will only work on a unix system because you cant just execute any file in windows
// windows = '.' dot is not a command
// mingwin = file opens in notepad.

//running with no params should show available functions
assert(stripos(`./run_TestSubject.php`,'functions available'));

//[ can execute ]
assert(stripos(`./run_TestSubject.php simple`,'TestSubject::simple was executed'));

//[ cant execute ]
//should not be able to pass arguments toa function that are not defined
assert(stripos(`./run_TestSubject.php simple with extra params sould cause an error`,'TestSubject::simple was executed'));
