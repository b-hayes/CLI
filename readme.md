# CLI
Quickly build interactive Command-line applications in PHP with 0 effort.

## Installation
`composer require b-hayes/cli`

## Usage.
Simply define a PHP class and inject it into the CLI wrapper 😎.
```php
(new \BHayes\CLI\CLI( $yourClass ))->run();
```
All your public class methods are now available as terminal commands.

Now you can just build your class methods instead of managing the interface. 👍

## Behaviours.
Here is what happens when CLI runs your class object.

- Public methods of your Class become executable commands.
- Automatic usage messages guiding the user on how to execute your class methods.
- Anything returned by a method is printed and no output is suppressed.
- When an object is returned, only public properties are printed. 
- Required methods parameters will be enforced.
- Scalar data types for method parameters will be enforced (try it).
- Prevents the user from passing too many arguments unless the method is variadic. (Php allows it, but I don't.)
- Help `--help` option will display your doc blocks if you have them.
- Public vars/properties of your class become options/flags.
  
If your class implements [`__invoke()`](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) or you pass in an anonymous function/closure
then your app immediately executes without the need for the user to type a method/command name.

## Additional Features.
- Functions are provided for easily reading inputs, prompts, and confirmations and do not require PHP to have the readline extension installed.
- Functions for generating/printing coloured strings.
- Throwable responses for halting execution with coloured success/error/warning messages for the user.
- Run any class by name in the terminal with the provided bin. 😲

## Getting started example.
For those unfamiliar with command-line scripts...
Make a file with a shebang line (#!) at the top that tells your shell to run this with PHP.

```php
#!/usr/bin/env php
<?php //        👆 important 👇 
require_once __DIR__ . '/../vendor/autoload.php';
//just using anonymous class as a quick example, can be any class.
$yourClass = new Class() {
    function hello(int $number = 0) {
        if ($number > 10) throw new \BHayes\CLI\UserErrorResponse("$number is too big for me!");
        if ($number) return "You gave me the number $number";
        return 'Hi ' . \BHayes\CLI\CLI::prompt('Enter your name', `git config user.name`);
    }
};

(new \BHayes\CLI\CLI( $yourClass ))->run();
```

Next, make the file executable:
```chmod +x myAwesomeNewCliApp```

Now you can run it as a terminal application!
```
./myAwesomeNewCliApp
```
CLI will guide the terminal user on how to run the available methods of your class.

### Windows
For those in windows who want to use powershell/cmd you will have to also make a batch file in the same location:
```cmd
php %~dp0/myAwesomeNewCliApp -- %*
```

### Start a collection.
I recommend keeping these files in a `/bin` folder in a personal project where
all your awesome CLI applications will live
and use git to synchronize them across your computers. 😉

## Errors and Exceptions
All errors are caught and suppressed with a generic error message
unless debug mode is used (see debug mode) or UserResponse exceptions are thrown.

Any UserResponse Exception with success code of 0 will simply print the success message,
regardless of debug mode.

CLI also detects and adjusts the default PHP error reporting config to prevent errors spitting output to the
terminal twice in debug mode.

## Responses to the user.
You can display messages to the user however you want:
- by echoing strings yourself and exiting manually.
- by returning a string, array or anything else to print. (exits with code 0 success)
- by throwing UserResponse exceptions. (recommended for errors)

I recommend using the provided UserResponse exception family for errors,
so you don't have to print the message with a new line and then exit with
a nonzero code manually.
```php
throw new \BHayes\CLI\UserResponse('This has exit code 1 and no coloured output');
throw new \BHayes\CLI\UserErrorResponse('Exit code 1 and text is printed in RED');
throw new \BHayes\CLI\UserWarningResponse('Exit code 1 and text is printed in YELLOW');
//all responses have an exit code of 1 by default except this one 👇
throw new \BHayes\CLI\UserSuccessResponse('Exit code 0 and text is printed in GREEN');
```

## Options/Flags
Based on the [POSIX](https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html)
standard CLI support short options `-o` and long options `--longOption`.

Options are mapped to public class variables defined in your class.
EG. If you want to have a --cats option for cats mode simply define the property
`public cats` and then simply check if it's true.
```php
if($this->cats) { echo "Cat mode enabled!"; }
```

*Note CLI currently does not support options with arguments (planned for php7.4 and higher).*

### Reserved Options.
CLI has reserved some options.
- --help. Just prints related doc blocks and exits without running your class.
- --debug. Debug mode, if enabled no exceptions/errors are suppressed.
  Your app can still use the debug option for its own purposes too.
- -i does nothing but can not be used by your app.
  I have reserved it for the "interactive mode" that I am thinking about building in the future.

## Example showcase.

Save this example as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`

Play with this to see a showcase of how type hinting dock blocks required and optional params
user prompts and different outputs etc are used.

```php
<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnusedPrivateMethodInspection */

declare(strict_types=1);

namespace BHayes\CLI\Test;

use BHayes\CLI\UserErrorResponse;
use BHayes\CLI\UserResponse;
use BHayes\CLI\UserSuccessResponse;
use BHayes\CLI\UserWarningResponse;

/**
 * Class TestSubject
 *
 * This is just to test what methods and params on a class via CLI.
 *
 * @package BHayes\CLI\Test
 */
class TestSubject
{
    public $a;
    public $b;
    public $c;

    public $apple;
    public $banana;
    public $carrot;

    public $debug;

    private $privateProperty = 'This should not be seen!';

    public function __construct()
    {
        return __METHOD__. " was executed!\n";
    }

    public function simple()
    {
        echo __METHOD__ , " was executed!";
        var_dump(func_get_args());
    }

    public function requiresTwo($required, $requiredAlso)
    {
        echo __METHOD__ , " was executed with params $required $requiredAlso";
        var_dump(func_get_args());
    }

    public function requiredAndOptional($required, $optional = null)
    {
        echo __METHOD__ , " was executed with $required, $optional";
        var_dump(func_get_args());
    }

    public function allOptional(string $optionalString = '', int $optionalInt = 5, object $optionalObject = null)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    private function aPrivateMethod()
    {
        echo __METHOD__ . " was executed!";
    }

    protected function aProtectedMethod()
    {
        echo __METHOD__ . " was executed!";
    }

    public function requiresInt(int $mustBeInt)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function requiresBool(bool $mustBeBool)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function requiresFloat(float $mustBeFloat)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function throwsAnError()
    {
        throw new \Error(__METHOD__ . " hates you!");
    }

    public function typedVariadicFunction(int ...$amounts)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function binCheck(int $exitCode)
    {
        echo __METHOD__ . " was executed!";
        var_dump(func_get_args());
        echo "\n";//because we are about to exit before cli can add the new line on the end of the output
        exit($exitCode);
    }

    /**
     * This method is used to test the --help function.
     * It has a doc block that should be displayed to the user.
     *
     */
    public function helpCheck()
    {
        echo __METHOD__, " was executed!";
    }

    public function noHelpCheck()// this one has no doc block to display
    {
        echo __METHOD__, " was executed!";
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserResponse()
    {
        throw new UserResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserWarning()
    {
        throw new UserWarningResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserError()
    {
        throw new UserErrorResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserSuccess()
    {
        echo __METHOD__, " was executed!";
        throw new UserSuccessResponse();
    }

    public function checkOptions()
    {
        echo __METHOD__, " was executed!\n";
        foreach ($this as $property => $value) {
            echo $property,": ";
            var_dump($value);
        }
    }

    public function dumpGlobals(...$args)
    {
        echo __METHOD__, " was executed!";
        //the global arv should remain unmodified.
        global $argv;
        var_dump($argv);
    }

    public function __toString()
    {
        return __METHOD__. " was executed!\n";
    }

    public function returnSelf(): TestSubject
    {
        return $this;
    }

    public function isString(): bool
    {
        return true;//is_string($this);
    }
}

```

## Advanced/edge case usage.

### My app should just run without "command"s.
Let's say your app is like a shell script and should just execute, but you still
want CLI to type match arguments and provide options.

Simply pass in an invokable object.
Either pass in a [class that implements the `__invoke()`method](test/run_invokableClass.php)
or by passing in an [anonymous function/closure](test/run_function.php).

The function or `__invoke()` method will immediately execute when the app runs however,
you can still require arguments of specific types, and the user can see help the same
as any other method/"command".

Note that CLI will not use __invoke or closure names in help docs to avoid,
showing technical jargon to the user.

### Forced debug mode.
During development, you may wish to always run in debug mode without typing --debug.
```php
$cli->run(true);
```
Or you may wish to prevent debug mode from working at all
WARNING: Doing this removes the --debug option. If the user types --debug now the application won't run because it's now an invalid option.

If you DO want both CLI and your class to receive the debug option by default simply
add it to the global argv before it runs.
```php
global $argv; //this is a built in var where php puts command line inputs
$argv[] = '--debug'; //manually add the --debug input as if the user typed it
$cli->run();
```

You can also prevent the debug option from working on CLI by specifying false in the run method.
This means --debug option has no effect but can still be used
```php
$argv[] = '--debug'; //this will get passed to your application but have no effect on CLI
$cli->run(false);//because debug has been explicitly disabled at run time.
```

### Custom exceptions for user responses.
For many reasons, you may prefer to avoid your class being dependent on CLI UserResponse exceptions,
and want to throw user response exceptions of your own.

You can give CLI a list of custom response exception types before it runs.
```php
new \BHayes\CLI\CLI($yourClass, [MySpecialUserException::class, MyOtherException::class]);
```
These and any Exceptions that inherit them will be treated the same UserResponse exceptions,
however, the exit code will not be used if it is 0.

This is because the default code of \Exception is 0 and nobody thinks about
them potentially getting used as exit codes when building a PHP application.

So I have forced non 0 exit codes, so you can continue to not think about it lol.

For success, you can just do nothing, or return a string.

### Force exit with 0.
For whatever reason, you may want your app to always return 0.
Simply force debug mode and exit manually in a try-catch.

```php
try{
    $cli->run(true);
    } catch(Throwable $e) {
    //do your logging or special handling etc.
    exit(0);//and then exit with 0 manually.
    }
```

### CLI can run itself.
If you don't inject a class CLI runs itself allowing you to use its prompt and
colour print methods in bash scripts etc.
I've provided vendor bin export for this purpose.
```bash
./vendor/bin/cli prompt Name? `git config user.name`
```
You could install the package globally:
```
composer global require b-hayes/cli
cli `
```
for all your other scripts to access shell commands
to be able to use these if you want.

The 'run' method won't do anything in this context however, 
you can...

### Run any class by name.
This is great for adhoc testing on random class objects in your project.
eg.
```bash
./vendor/bin/cli BHayes\\CLI\\Colour string Hello 93
```
If the first input exactly matches the name of a class that can be auto-loaded and instantiated,
then it will consume that argument and run the class.

It will fail if the Class constructor has dependencies however,
I have thought about adding the ability for CLI to use a dependency resolver in the future.

## Feedback is welcome.
This is my first public composer package be gentle 😅.

If this project gives you a 1up🍄 and would like to see some more automated features then,

please [buy me a coffee. ☕😃 ![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=bradozman%40gmail.com&item_name=%E2%98%95+Turning+Coffee++into+Code.&currency_code=AUD)
