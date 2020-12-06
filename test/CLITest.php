<?php

declare(strict_types=1);

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
     * This is a helper to ensure my tests cover essential assertions.
     *
     * @param string $method
     * @param string $arguments
     * @param mixed  ...$expectedErrorMessages
     *
     * @return false|string
     */
    private function assertFailureToExecute(string $method, string $arguments, string ...$expectedErrorMessages)
    {
        ob_start();
        passthru("php test/run_TestSubject.php $method $arguments", $exitCode);
        $output = ob_get_clean();

        self::assertStringContainsString(
            $method,
            $output,
            "Failure message does not contain the correct method name. Expected '$method'"
        );
        foreach ($expectedErrorMessages as $expectedErrorMessage) {
            self::assertStringContainsString(
                $expectedErrorMessage,
                $output,
                "Failure message does not contain appropriate message component. Expected: '$expectedErrorMessage'"
            );
        }
        self::assertStringContainsString("\n", $output, "Output should always end in a new line.");

        if (strpos($arguments, '--debug') === false) {
            //todo: assert that the fill class name is not included in error messages displayed to the user.
            // and that stack traces and uncaught errors are never shown to a normal user.
        }

        self::assertNotEquals(0, $exitCode, "A failed command should exit with an error code!");

        return $output;
    }

    /**
     * This is a helper to ensure my tests cover essential assertions.
     *
     * @param       $method
     * @param       $arguments
     * @param mixed ...$expectedResponseMessages
     *
     * @return false|string
     */
    private function assertSuccessfulExecution($method, $arguments, ...$expectedResponseMessages)
    {
        ob_start();
        passthru("php test/run_TestSubject.php $method $arguments", $exitCode);
        $output = ob_get_clean();

        //method should have been executed.
        self::assertStringContainsString(
            "$method was executed",
            $output,
            "Expected '$method' to be executed and let us know that it was."
        );

        foreach ($expectedResponseMessages as $responseMessage) {
            self::assertStringContainsString(
                $responseMessage,
                $output,
                "Response does not contain appropriate message component. Expected: '$responseMessage'"
            );
        }

        self::assertStringContainsString("\n", $output, "Output should always end in a new line.");

        self::assertEquals(0, $exitCode, "A successful command should exit with code 0!");

        return $output;
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
     * Assert Behaviour: execute function by name.
     */
    public function testRunSimple()
    {
        self::assertSuccessfulExecution('simple', '');
    }

    /**
     * Failure if first argument does not match function name.
     */
    public function testMethodDoesntExistOrCanNotBeAccessed()
    {
        $output = $this->assertFailureToExecute(
            'doesntExist',
            'arguments should not matter',
            'is not a recognized command',
            //Failure message should include a list of available functions.
            "Functions available:\n",
            ...get_class_methods(TestSubject::class)//all the public methods defined on the class.
        );
        //only public methods should not be listed.
        self::assertStringNotContainsString($output, 'aPrivateMethod');
        self::assertStringNotContainsString($output, 'aProtectedMethod');

        //same response if you try to execute a private method
        $this->assertFailureToExecute(
            'aPrivateMethod',
            'arguments should not matter',
            'is not a recognized command',
            //Failure message should include a list of available functions.
            "Functions available:\n",
            ...get_class_methods(TestSubject::class)//all the public methods defined on the class.
        );

        //same response if you try to execute a protected method
        $this->assertFailureToExecute(
            'aProtectedMethod',
            'arguments should not matter',
            'is not a recognized command',
            //Failure message should include a list of available functions.
            "Functions available:\n",
            ...get_class_methods(TestSubject::class)//all the public methods defined on the class.
        );
    }

    public function testRequiredParams()
    {
        $this->assertSuccessfulExecution('requiresTwo', 'one two', 'one two');
        $this->assertFailureToExecute('requiresTwo', 'one', 'Too few arguments');
    }

    public function testTooManyArguments()
    {
        $this->assertFailureToExecute('requiresTwo', 'one two three', 'Too many arguments');
    }

    public function testOptionalArguments()
    {
        self::assertSuccessfulExecution('requiredAndOptional', 'required optional');
        self::assertSuccessfulExecution('requiredAndOptional', 'required');
    }

    public function testInt()
    {
        $this->assertSuccessfulExecution('requiresInt', '5');
        $this->assertFailureToExecute('requiresInt', 'five', 'must be of the type int');
        $this->assertFailureToExecute('requiresInt', '5.5', 'must be of the type int');
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
