<?php

namespace BHayes\CLI\Test;

use BHayes\CLI\CLI;
use PHPUnit\Framework\TestCase;

class CLITest extends TestCase
{

    /**
     * @var CLI
     */
    private $cli;

    public function setUp(): void
    {
        $this->cli = new CLI();
        self::assertInstanceOf(CLI::class, $this->cli);
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
        $prompt = 'enter your name';
        $this->cli->inputStream = __DIR__ . '/../test/test_input';
        self::expectOutputString($prompt);
        $input = $this->cli->prompt($prompt);
        self::assertEquals('bill', $input);
    }

    public function testRun()
    {
        self::assertTrue(true);
    }
}
