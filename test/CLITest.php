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
        $this->cli->inputStream = __DIR__ . '/../test/test_input';
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

    /**
     * Test the different behaviours of the prompt command with a replacement input stream
     */
    public function testPrompt()
    {
        $prompt = 'enter your name';
        $this->setInput('bill');

        //test that the prompt and the return value of prompt is correct
        ob_start();
        $input = $this->cli->prompt($prompt);
        $output = ob_get_clean();
        self::assertEquals($prompt, $output);
        self::assertEquals('bill', $input);
    }

    public function testRun()
    {
        self::assertTrue(true);
    }

    private function setInput($response): void
    {
        $this->cli->inputStream = 'data://text/plain,' . $response;
    }
}
