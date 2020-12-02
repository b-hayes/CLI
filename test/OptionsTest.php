<?php

namespace BHayes\CLI\Test;

use BHayes\CLI\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testConstruct()
    {
        $options = new Options();
         self::assertInstanceOf(Options::class, $options);
    }
}
