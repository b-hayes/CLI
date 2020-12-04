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
        $fp = fopen('data://text/plain,' . 'a value', "r");
        $rtrim = rtrim(fgets($fp, 1024));
        self::assertEquals('a value', $rtrim);
    }

    /**
     * Test the different behaviours of the prompt command with a replacement input stream.
     * Note: there is an expectOutput() function in phpunit but it only works once so I didnt use it.
     */
    public function testPrompt()
    {
        $prompt = 'enter your name';

        //prompt must echo the prompt message and return the input received
        $this->setInput('bill');
        ob_start();
        $input = $this->cli->prompt($prompt);
        $output = ob_get_clean();
        self::assertEquals($prompt, $output);
        self::assertEquals('bill', $input);

        //trailing white space and new line chars are removed from return value.
        $this->setInput("bill higgins    \n \n \r\n");
        ob_start();
        $input = $this->cli->prompt($prompt);
        $output = ob_get_clean();
        self::assertEquals($prompt, $output);
        self::assertEquals('bill higgins', $input);

        //an empty string will be returned from pressing only enter
        $this->setInput("\n");
        ob_start();
        $input = $this->cli->prompt($prompt);
        $output = ob_get_clean();

        self::assertEquals($prompt, $output);
        self::assertEquals('', $input);

        //default value must be displayed in the prompt with square brackets and be returned when user preses enter
        $this->setInput("\n");
        ob_start();
        $defaultValue = 'a default value';
        $input = $this->cli->prompt($prompt, $defaultValue);
        $output = ob_get_clean();
        self::assertEquals("$prompt [$defaultValue]", $output);
        self::assertEquals($defaultValue, $input);

        //all return values will be lowercase by default
        $this->setInput("UPPER CASE");
        ob_start();
        $input = $this->cli->prompt($prompt, $defaultValue);
        $output = ob_get_clean();
        self::assertEquals('upper case', $input);

        //default value is used the case IS STILL converted to lower by default
        $this->setInput("\n");
        ob_start();
        $defaultValue = 'A DEFAULT VALUE IN UPPER CASE';
        $input = $this->cli->prompt($prompt, $defaultValue);
        $output = ob_get_clean();
        self::assertEquals("$prompt [$defaultValue]", $output);
        self::assertEquals('a default value in upper case', $input);

        //case is always preserved when convert to lowercase is disabled.
        $this->setInput("\n");
        ob_start();
        $defaultValue = 'A Default Mixed Case Value';
        $input = $this->cli->prompt($prompt, $defaultValue, false);
        $output = ob_get_clean();
        self::assertEquals("$prompt [$defaultValue]", $output);
        self::assertEquals($defaultValue, $input);

        //case can be preserved without being forced to sue a default value
        $this->setInput("A Mixed Case Value\n");
        ob_start();
        $input = $this->cli->prompt($prompt, '', false);
        $output = ob_get_clean();
        self::assertEquals("$prompt", $output);
        self::assertEquals("A Mixed Case Value", $input);
    }

    /**
     * Used only for testing the prompt function.
     *
     * @param $stringOrFile
     */
    private function setInput($stringOrFile): void
    {
        if (is_file($stringOrFile)) {
            $this->cli->inputStream = $stringOrFile;
            return;
        }
        $this->cli->inputStream = 'data://text/plain,' . $stringOrFile;
    }

    //NOTE: Test all the functionality of cli on a test subject.

    public function testRunSimple()
    {
        $output = `php test/run_TestSubject.php simple`;
        self::assertEquals(TestSubject::class . '::simple was executed' . "\n", $output);
    }

    public function testRequiredParams()
    {
        $one = 'one';
        $two = 2;
        $output = `php test/run_TestSubject.php requiresTwo $one $two`;
        self::assertEquals(TestSubject::class . "::requiresTwo was executed with params $one $two\n", $output);
    }

    public function testRequiredParamsMissingWillFailWithAppropriateMessage()
    {
        $one = 'one';
        $two = '';
        $output = `php test/run_TestSubject.php requiresTwo $one $two`;
        self::assertEquals("Too few arguments to function requiresTwo, 1 passed and exactly 2 expected\n", $output);
    }

    public function testTooManyParamsFailsWithAppropriateMessage()
    {
        $one = 'one';
        $two = '2';
        $three = 'three';
        $output = `php test/run_TestSubject.php requiresTwo $one $two $three`;
        self::assertEquals("Too many arguments. Function requiresTwo can only accept 2\n", $output);
    }
}
