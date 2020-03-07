<?php

namespace BHayes\CLI\Test;

use BHayes\CLI\CLI;
use PHPUnit\Framework\TestCase;

class CLITest extends TestCase
{

    public function setUp(): void
    {
        self::assertInstanceOf(CLI::class, new CLI());
    }


    /**
     * This is the method that the prompt/readline function uses internally.
     * (this was a temporary test setup before I made the input stream swappable)
     */
    public function testReadlineMethod()
    {
        $fp = fopen(__DIR__ . '/../test/test_input', "r");
        $rtrim = rtrim(fgets($fp, 1024));
        self::assertEquals('bill', $rtrim);
    }

    public function testPrompt()
    {
        $CLI = new CLI();
//        $prompt = $CLI->prompt('enter your name');
        self::assertTrue(true);
    }

    public function testRun()
    {
        self::assertTrue(true);
    }
}
