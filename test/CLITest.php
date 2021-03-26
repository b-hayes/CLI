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
        if (!empty($method)) {
            self::assertStringContainsString(
                "$method was executed",
                $output,
                "Expected '$method' to be executed and let us know that it was."
            );
        }

        //method should repeat all the values it was given so we know it received the correct data
        foreach (explode(' ', $arguments) as $arg) {
            if (substr($arg, 0, 1) === '-') {
                //this is a --option / -o so it wont be used as an argument.
                continue;
            }
            self::assertStringContainsString(
                $arg,
                $output,
                "Expected argument '$arg' to be repeated back to me but it wasn't."
            );
        }

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
     * Used only for testing the prompt function.
     *
     * @param $stringOrFile
     *
     * @return string
     */
    private function setupInput($stringOrFile): string
    {
        if (is_file($stringOrFile)) {
            return $stringOrFile;
        }
        return 'data://text/plain,' . $stringOrFile;
    }

    /**
     * This is the method that the prompt/readline function uses internally.
     * (this was a temporary test setup before I made the input stream swappable)
     */
    public function testReadlineMethod()
    {
        $result = CLI::readline(null, 'data://text/plain,' . 'a value');
        self::assertEquals('a value', $result);
    }

    /**
     * Assert the expected behaviour of the prompt command.
     */
    public function testPromptWith2Lines()
    {
        $handle = fopen('data://text/plain,' . "Bill\nTed\n\n  Kate Williams      \n", 'r');
        ob_start();
        $one = CLI::prompt('enter your name', 'CATS', false, $handle);
        $two = CLI::prompt('enter your name', 'CATS', true, $handle);
        $three = CLI::prompt('enter your name', 'CATS', true, $handle);
        $four = CLI::prompt('enter your name', 'CATS', false, $handle);
        $obGetClean = ob_get_clean();
        self::assertTrue(
            substr_count($obGetClean, 'enter your name') === 4 &&
            substr_count($obGetClean, 'CATS') === 4,
            'The prompt message should appear with the default response shown every time.'
        );
        self::assertEquals('Bill', $one);//case sensitive
        self::assertEquals('ted', $two);//converted to lowercase
        self::assertEquals('cats', $three);//default value is returned in lowercase
        self::assertEquals('Kate Williams', $four);//extra white space is trimmed
    }

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

    public function testVariadic()
    {
        $this->assertSuccessfulExecution('typedVariadicFunction', '');
        $this->assertSuccessfulExecution('typedVariadicFunction', '1');
        $this->assertSuccessfulExecution('typedVariadicFunction', '1 2');
        $this->assertSuccessfulExecution('typedVariadicFunction', '1 2 3');
        $this->assertFailureToExecute('typedVariadicFunction', '1 2 three');
        $this->assertFailureToExecute('typedVariadicFunction', '1 two 3');
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

    public function testUsage()
    {
        $this->assertSuccessfulExecution(
            '',
            '',
            //it should mention the following
            'usage:',
            'Functions available:',
            '--help'
        );
    }

    public function testHelp()
    {
        $this->assertSuccessfulExecution('', '--help');
        $this->assertSuccessfulExecution('', '--help helpCheck', 'This method is used to test the --help function.');
        $this->assertSuccessfulExecution('', '--help noHelpCheck', 'No documentation');
    }

    public function testBin()
    {
        //the bin file is able to run a class by name if specified

        //weather or not the user would scape slashes is base don the terminal shell they use.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //using Windows to test so we dont need the added slashes
            $className = TestSubject::class;
        } else {
            //we need to escape the slashes for unix shells
            $className = addslashes(TestSubject::class);
        }

        $this->command = 'php bin/cli ' . $className;
        $this->assertSuccessfulExecution('binCheck', '0');
        $this->assertFailureToExecute('binCheck', '1');
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

    //TODO: PHPv8 support:
    //  test a method with the MIXED types when moving to php8
    //  test a method with the UNION types when moving to php8
}
