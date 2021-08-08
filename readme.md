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

If your class implements [__invoke()](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke)
or you pass in an [anonymous function](https://www.php.net/manual/en/language.types.callable.php) instead of a class,
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
//Just using anonymous class as a quick example. You can do this too when you just want to make a quick cli tool.
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

### 💡 Start a collection.
I recommend keeping your cli applicant in a personal project tracked by version control where
all your awesome CLI tools will live and add its `/bin` folder to your system path.

I do this and use git to synchronize my scripts across computers. 😉

## Errors and Exceptions
All errors are caught and suppressed with a generic error message,
unless debug mode is used (see debug mode) or UserResponse exceptions are thrown.

Any UserResponse Exception with success code of 0 will simply print the success message,
regardless of debug mode.

CLI also detects and adjusts the default PHP error reporting config to prevent errors spitting output to the
terminal twice in debug mode.

## Responses to the user.
You can display messages to the user however you want:
- by throwing UserResponse exceptions. **RECOMMENDED**
- by echoing strings yourself and exiting manually. Not recommended.
  - I advise against this and esp against the use of die. Exit codes are useful and don't want to forget them.
- by returning a string, array or object to print.
  - When returning data CLI always exits with code 0 success.
  - If anything other than a string is returned it will be printed as a JSON string.
  - If an object is returned only public properties are printed.

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
During development, you may wish to always run in debug mode without typing --debug all the time.
Simple pass true for the 3rd param in the constructor.
```php
$cli = new CLI($class, [], true);
```

Or you may wish to remove the debug option entirely by passing in `false` instead.
```php
$cli = new CLI($class, [], true);
```
If the user types --debug now the application won't run because it's now an invalid option.

If you want to force debugging but have your class to receive the debug option
add it to the global argv before it runs.
```php
global $argv; //this is a built-in var where php puts command line inputs
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

### CLI can run itself.
If you don't inject a class CLI runs itself allowing you to use its prompt and
colour print methods in bash scripts etc.
```bash
./vendor/bin/cli prompt Name? `git config user.name`
```
You can install the package globally:
```bash
composer global require b-hayes/cli
```
Add the global project bin dir to your path, and you can use it globally.
```bash
cli prompt Name? `git config user.name`
```

### Run any class by name.
This is great for adhoc testing on random class objects in your project.
It can also automatically add the projects top level namespace for you. Eg.
```bash
cli MyClass
# Instead of
cli MyVendor\\MyProject\\MyClass
```
If the first input matches the name of a class it will attempt to load it.
It does this by reading the PSR-4 autoload paths from your `composer.json`.

It will fail if the Class constructor has dependencies however,
I may add a way for it to use dependency containers in the future.

## Feedback is welcome.
This is my first public composer package be gentle 😅.

If this project gives you a 1up🍄 and would like to see some more automated features then,

please [buy me a coffee. ☕😃 ![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=bradozman%40gmail.com&item_name=%E2%98%95+Turning+Coffee++into+Code.&currency_code=AUD)
