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

    /**
     * This is a helper to ensure that I am asserting failure messages for the correct function name.
     *
     * @param       $output
     * @param       $methodNameCalled
     * @param mixed ...$expectedErrorMessages
     */
    private function assertFailureToExecute($output, $methodNameCalled, ...$expectedErrorMessages)
    {
        self::assertStringContainsString(
            $methodNameCalled,
            $output,
            "Failure message does not contain the correct method name. Expected '$methodNameCalled'"
        );
        foreach ($expectedErrorMessages as $expectedErrorMessage) {
            self::assertStringContainsString(
                $expectedErrorMessage,
                $output,
                "Failure message does not contain appropriate message component. Expected: '$expectedErrorMessage'"
            );
        }
        self::assertStringContainsString("\n", $output, "Output should always end in a new line");
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

    //NOTE: From here on we test all the functionality of cli on a test subject class with functions for us to run.

    /**
     * Assert Bahavour: execute function by name.
     * The first cli argument will execute a function by the same name on the test subject.
     */
    public function testRunSimple()
    {
        $output = `php test/run_TestSubject.php simple`;
        self::assertEquals(TestSubject::class . '::simple was executed' . "\n", $output);
    }

    /**
     * If a function with the same name as argument one does not exist then failure message is given,
     * along with a list of available functions.
     */
    public function testMethodDoesntExist()
    {
        $output = `php test/run_TestSubject.php doesntExist`;
        $this->assertFailureToExecute(
            $output,
            'doesntExist',
            'is not a recognized command',
            "Functions available:\n",
            ...get_class_methods(TestSubject::class)//all the public methods defined on the class.
        );
        //private methods should not be listed
        self::assertStringNotContainsString($output, 'shouldNotBeSeen');
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
        $methodName = 'requiresTwo';
        $one = 'one';
        $two = '';
        $output = `php test/run_TestSubject.php $methodName $one $two`;
        $this->assertFailureToExecute($output, $methodName, 'Too few arguments');
    }

    /**
     * Assert Behaviour: Too many arguments.
     * You can not overload a function with more arguments than specified like you normally can in php.
     */
    public function testTooManyParamsFailsWithAppropriateMessage()
    {
        $methodName = 'requiresTwo';
        $one = 'one';
        $two = '2';
        $three = 'three';
        $output = `php test/run_TestSubject.php $methodName $one $two $three`;
        $this->assertFailureToExecute($output, $methodName, 'Too many arguments');
    }

    public function testRequiredAndOptional()
    {
        $one = 'big';
        $two = 'cats';
        $output = `php test/run_TestSubject.php requiredAndOptional $one $two`;
        self::assertEquals(TestSubject::class . "::requiredAndOptional was executed with $one, $two\n", $output);
    }

    // \/ SANITY CHECKS and NOTES \/

    public function testNeverUseReflectionToExecute()
    {
        $reflectionClass = new \ReflectionClass(CLI::class);
        $fileName = $reflectionClass->getFileName();
        $definition = file_get_contents($fileName);
        self::assertTrue(
            stripos($definition, '->invoke(') === false,
            "Never use reflection to execute the subject method! Please read comments in this test."
        );
        /**
         * So because I'll likely be away from this project for some time I may forget why.
         *  - reflection bypasses strict type declaration.
         *  - reflection will break injected dependencies on the subject if provided whole by the user.
         *
         * However I'll likely need to use the reflection method due to the complex behaviour I am after.
         *
         * I wont be able to rely on catching type and argument errors because:
         *  - in order to use strict types I'll have to manually convert them from strings before execution.
         *  - i cant simply use json_encode as strings will break, I need to manually check the types required.
         *  - I also cant assume that the type/arg errors come from the user passing in bad arguments because:
         *      - uncaught errors of the same types could occur in the subject
         *          and these internal should be hidden from a user.
         *      - even if checking the method name within the error message can not be relied on due to potential
         *         recursion or simple being executed again at another level.
         *
         * TODO: all these edge cases should be tested.
         */
    }

    public function testForBreakingChanges()
    {
        self::assertTrue(true);
        //todo: alert me if I make any breaking changes once I tag a major version.
        // (check out your old blueprints method that used to do this)
    }
}
