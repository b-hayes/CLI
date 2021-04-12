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
- Required methods parameters will be enforced.
- Scalar data types for method parameters will be enforced (try it).
- Prevents the user from passing too many arguments unless the method is variadic. (Php allows it, but I don't.)
- Help `--help` option will display your doc blocks if you have them.
- Public vars/properties of your class become options/flags.
- Colours and emojis encouraged (try the provided CLI::printLine() or Colour::string() methods).

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

## Example.

Save this example as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`

Play with this to see a showcase of how type hinting dock blocks required and optional params
user prompts and different outputs etc are used.

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);//optional but good practice IMO. Google it.

use BHayes\CLI\CLI;
use BHayes\CLI\Colour;
use BHayes\CLI\UserErrorResponse;
use BHayes\CLI\UserResponse;
use BHayes\CLI\UserSuccessResponse;

require_once 'vendor/autoload.php'; //if installed via composer

/**
 * This is the documentation that will appear when you type --help.
 */
class Example
{

    /**
     * This one is easy to run.
     * try runMe with --help to see this text.
     */
    public function runMe(string $optional = null)
    {
        //either return output or just output directly its up to you.
        echo "I work with no arguments.";
        if ($optional !== null) {
            echo " But thanks for providing me with: ";
            var_dump($optional);
        }
    }

    /**
     * This command will only run when all the requirements are met.
     */
    public function tryMe(bool $bool, string $string, float $float, int $int)
    {
        return "You did it! You gave me bool a string, a float and an int.";
    }

    /**
     * This method will accept any number of string arguments while the
     * the others will fail if you pass them too many arguments.
     *
     * @param string ...$bunchOfStrings
     */
    public function variadic(string ...$bunchOfStrings)
    {
        echo "You said ";
        if (empty($bunchOfStrings)) {
            echo "nothing.";
        }
        print_r($bunchOfStrings);
        echo "\n";
    }

    /**
     * Demos prompts.
     *
     * @throws UserResponse
     */
    public function survey():string
    {
        if (! CLI::confirm('Shall we begin?')) {
            return "Cancelled";
        }
        $colour = CLI::prompt('Whats your favorite colour?');
        $colourCode = Colour::code($colour);
        throw new UserResponse("I love $colour too!", $colourCode, '☺');
    }


    /**
     * Tests the UserResponse throwable.
     *
     * @param bool|null $success
     * @throws UserResponse
     */
    public function throwsUserResponse(bool $success = null)
    {
        if ($success === true) {
            throw new UserSuccessResponse();//all params optional
        }
        if ($success === false) {
            throw new UserErrorResponse('Some error message user needs to see!');
        }
        throw new UserResponse('Try this again with true or false.');
    }

    /**
     * foo is now an option because it has been declared public.
     * @var bool
     */
    public $foo = false;

    /**
     * Run me with and without `--foo` and see the result.
     */
    public function bar()
    {
        var_dump($this->foo);
    }
};

$cli = new CLI(new Example());
$cli->run();
```

## Advanced/edge case usage.

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
You could install the package globally for all your other scripts to access shell commands
to be able to use these if you want.

The 'run' method won't do anything in this context however, 
you can...

### Run any class by name.
This is great for adhoc testing on random class objects in your project.
eg.
```bash
./vendor/bin/cli BHayes\\CLI\\Colour string Hello 93
```
If the first input exactly matches the name of a class that can be auto loaded and instantiated,
then it will consume that argument and run the class.

It will fail if the Class constructor has dependencies however,
I have thought about adding the ability for CLI to use a dependency resolver in the future.

## Feedback is welcome.
This is my first public composer package be gentle 😅.

If this project gives you a 1up🍄 and would like to see some more automated features then,

please [buy me a coffee. ☕😃 ![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=bradozman%40gmail.com&item_name=%E2%98%95+Turning+Coffee++into+Code.&currency_code=AUD)
