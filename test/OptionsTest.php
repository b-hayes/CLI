<?php

namespace BHayes\CLI\Test;

use BHayes\CLI\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function test__construct()
    {
        var_dump(ini_get('register_argc_argv'));
        $GLOBALS['argv'] = ['-f','filename'];
        var_dump(new Options());
        self::assertTrue(true);
    }
}
