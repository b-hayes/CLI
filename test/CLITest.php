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

    /**
     * @var string
     */
    private $command;

    public function setUp(): void
    {
        $this->command = "php test/run_TestSubject.php";
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
        passthru("{$this->command} $method $arguments", $exitCode);
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
        passthru("{$this->command} $method $arguments", $exitCode);
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
        $this->cli = new CLI();//moved here instead od setup since this is the only funciton needing it.
        // (dramatically increase speed of the rest of the unit tests)
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
        $this->assertFailureToExecute(
            'requiresTwo',
            'one',
            'Too few arguments',
            //the name of the params should be listed as part of help for the function
            'required',
            'requiredAlso'
        );
    }

    public function testTooManyArguments()
    {
        $this->assertFailureToExecute('requiresTwo', 'one two three', 'Too many arguments');
    }

    public function testOptionalArguments()
    {
        self::assertSuccessfulExecution('requiredAndOptional', 'required optional');
        self::assertSuccessfulExecution('requiredAndOptional', 'required');
        self::assertSuccessfulExecution('allOptional', '');
    }

    public function testInt()
    {
        $this->assertSuccessfulExecution('requiresInt', '5');
        $this->assertFailureToExecute('requiresInt', 'five', 'must be of the type int');
        $this->assertFailureToExecute('requiresInt', '5.5', 'must be of the type int');
        $this->assertFailureToExecute('requiresInt', '5five', 'must be of the type int');
        $this->assertFailureToExecute('requiresInt', 'five5', 'must be of the type int');
    }

    public function testBool()
    {
        $this->assertSuccessfulExecution('requiresBool', 'true');
        $this->assertSuccessfulExecution('requiresBool', 'false');
        $this->assertFailureToExecute('requiresBool', 'not_a_bool', 'must be of the type bool');
        $this->assertFailureToExecute('requiresBool', '1', 'must be of the type bool');
        $this->assertFailureToExecute('requiresBool', '0', 'must be of the type bool');
        $this->assertFailureToExecute('requiresBool', 'null', 'must be of the type bool');
    }

    public function testFloat()
    {
        $this->assertSuccessfulExecution('requiresFloat', '1.1');
        $this->assertSuccessfulExecution('requiresFloat', '1');
        $this->assertFailureToExecute('requiresFloat', '1f');
        $this->assertFailureToExecute('requiresFloat', 'one');
    }

    public function testInternalError()
    {
        $output = $this->assertFailureToExecute(
            'throwsAnError',
            '',
            'the program crashed'
        );
        self::assertStringNotContainsString(
            ' hates you!',
            $output,
            "❌ Internal error information should be hidden rom the user!"
        );

        //however with debug mode enabled we should provide the internal error details
        $this->assertFailureToExecute('throwsAnError', '--debug', 'throwsAnError hates you!');

        //A standard user should never see the real error message.
        $output = $this->assertFailureToExecute(
            'throwsAnError',
            '',
            'the program crashed. Please contact the developers'
        );
        // they should also never see the stack trace or file and line info.
        $reflectionClass = new \ReflectionClass(TestSubject::class);
        $fileName = $reflectionClass->getFileName();
        self::assertStringNotContainsString($fileName, $output);
        self::assertStringNotContainsString('stack trace', $output);
    }

    // \/ SANITY CHECKS and NOTES \/

    public function testNeverUseReflectionToExecute()
    {
        $reflectionClass = new \ReflectionClass(CLI::class);
        $fileName = $reflectionClass->getFileName();
        $definition = file_get_contents($fileName);
        self::assertTrue(
            stripos($definition, '->invoke(') === false,
            "Never use the reflection method to execute the subject method!"
        );
    }

    public function testForBreakingChanges()
    {
        self::assertTrue(true);
        //todo: alert me if I make any breaking changes once I tag a major version.
        // (check out your old blueprints method that used to do this)
    }

    public function testBin()
    {
        //the bin file is able to run a class by name if specified
        $this->command = 'php bin/cli ' . TestSubject::class;
        $this->assertSuccessfulExecution('binCheck', '0');
        $this->assertFailureToExecute('binCheck', '1');
    }
}
