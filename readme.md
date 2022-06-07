# CLI
Quickly build interactive Command-line applications in PHP with 0 effort.

## Installation
`composer require b-hayes/cli`

## Usage.
Simply define a PHP class and inject it into the CLI wrapper 😎.
```php
(new \BHayes\CLI\CLI( $yourClass ))->run();
```
Now you can just build your class methods, without managing inputs, exit codes or printing usage hints! 😲

![https://i.imgur.com/uu8gQBr.gif](https://i.imgur.com/uu8gQBr.gif)

## Behaviours.
Here is what happens when CLI runs your class object:

- Public methods are exposed as executable commands. 👍
- Required inputs and data types are automatically enforced. 🙂
- Automatically provides usage hints to guide the user. 😊
- Print anything that is returned. (public properties only if an object). 😃 
- Manages error messages with Shell exit codes! 😲
- Maps your public properties to options/flags. 🤯
- Easily print or return coloured strings and prompt for input/confirmations 😍

### Arguments.
Arguments directly map to function definitions. 
- Parameter types are strongly enforced (eg, bool must be 'true' or 'false' and int can not have decimal places).
- Prevents the user from passing too many arguments, unless it is ...variadic. (php allows it but I don't)

### Single function scripts.
If you implement [__invoke()](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) method
or you pass in an [anonymous function](https://www.php.net/manual/en/language.types.callable.php) instead of a class,
then your app immediately executes without the need for the user input.

### Options/Flags
Based on the [POSIX](https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html)
standard, short options `-o` and long options `--longOption`
are automatically created from public class properties.
```php
public $cats;
if($this->cats) { echo "Cat mode enabled!"; }
```

![https://i.imgur.com/mGr1PIj.gif](https://i.imgur.com/mGr1PIj.gif)

_CLI currently does not support options with arguments, yet._

I planned to support this for php7.4 and higher using typehints,
but not until I flesh out as much as I can for 7.2 users before moving on.

### Reserved Options.
CLI has reserved some options:
- --help. Just prints related doc blocks and exits without running your class. (might become prettier in future).
- --debug. Debug mode, if enabled all exceptions/errors and their stack traces are printed.
- -i does nothing, yet. (I have ideas for an interactive mode).

![https://i.imgur.com/EXuX9Jx.gif](https://i.imgur.com/EXuX9Jx.gif)

### Exit codes and Errors.
CLI will automatically return a non-zero exit code on failure.
Error output is suppressed unless you use `--debug` mode
or throw a [Response Exception](#Response Exceptions).

## Dependencies
CLI has no dependencies and does not force you to dependent on it in-case your class is also used for other things.

## Getting started example.
For those unfamiliar with shell scripts...

Make a file with a shebang line (#!) at the top that tells your shell to run this with PHP.

```php
#!/usr/bin/env php
<?php //        👆 important 👇
require_once __DIR__ . '/../vendor/autoload.php';
//Just using anonymous class as a quick single file example.
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

### Windows Users
For those who want to use powershell && cmd. Make a batch file containing:
```
php %~dp0/myAwesomeNewCliApp -- %*
```

### 💡 Start a collection.
I recommend keeping your cli app in a personal project,
and add its `/bin` folder to your system path
so all your CLI apps are globally accessible.

I do this and use git to synchronize my personal devtools across computers. 😉

## Error handling.
CLI catches all errors by default and presents a generic program crashed message.
You can use `--debug` to see the stack trace if needed.

### Response Exceptions
An error caused by input or an external factor,
and you need to return a user response while also
returning/remembering to use a **non-zero** exit code
so the parent shell process knows the command has failed.

Just throw a UserResponseException from anywhere in your stack and CLI takes care of it.

To make life easy I have provided several, including a success response,
so you don't have to return two values all the way up the stack.

```php
throw new \BHayes\CLI\UserResponse('This has an exit code of 1 and no coloured output');
throw new \BHayes\CLI\UserErrorResponse('Exit code 1 and text is printed in RED');
throw new \BHayes\CLI\UserWarningResponse('Exit code 1 and text is printed in YELLOW');
//IMPORTANT: all responses have an exit code of 1 by default except this one 👇
throw new \BHayes\CLI\UserSuccessResponse('Exit code 0 and text is printed in GREEN');
```

You can also specify colours, emojis and more specific error codes.
```php
throw new \BHayes\CLI\UserResponse('Printer failed!',\BHayes\CLI\Colour::BG_LIGHT_MAGENTA, '🖨🔥', 221);
```
The separate icon string is for globally
disabling emojis output on terminals with no UTF-8 support (in the future).

Error code is last since its usually only important to have 0 or non-zero to indicate success/failure.
(besides php 8 allows us to bypass order of arguments now)

### Custom Response Exceptions.
You might want to specify your own exceptions to be treated as CLI responses instead of the provided ones.
Simply pass in a list of class names in the constructor:
```php
$cli = new CLI($class, [
    MyCustomException::class,
    SomeSpecificThirdPartyException::class,
]);
```
**WARNING:** Remember that by default all PHP and Third party exceptions will have 0 as their code
and report a success response as nobody every thinks of them being used for CLI exit codes.
You will have to be vigilant and account for this manually.

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
**Side effect:** If the user types --debug now the application won't run because it's now an invalid option.

To avoid this you can simulate someone typing it:
```php
global $argv; //this is a built-in var where php puts command line inputs
$argv[] = '--debug'; //manually add the --debug input as if the user typed it
$cli->run();
```

You can also prevent the debug option entirely while still passing it to your app as a property.
```php
$argv[] = '--debug'; //this will get passed to your application but have no effect on CLI
$cli->run(false);//because debug has been explicitly disabled at run time.
```
(supporting extreme edge case uses here and this will probably change.)

## CLI as a Global tool.

CLI comes with a vendor bin to run itself exposing
its prompt and colour print methods in the terminal:
```bash
./vendor/bin/cli prompt Name? `git config user.name`
```

You can install the package globally:
```bash
composer global require b-hayes/cli
```

and then run it from anywhere without specifying the path:
```bash
cli prompt Name? `git config user.name`
```

### Run any class by name.
This is great for adhoc testing on random class objects in a project your working on.
It can also automatically add the projects top level namespace for you.
```bash
cli MyClass
# Instead of
cli MyVendor\\MyProject\\MyClass
```
It does this by reading the `composer.json` file in the current directory.

It will fail if the Class has a constructor with argument
(I may add a way for it load dependencies in the future).

## Running other shell commands.
exec() is a wrapper for [passthru](https://www.php.net/manual/en/function.passthru.php)
but, throws an Exception on failure.
```php
CLI::passThru('sudo apt install php');
```

This provides an eay way to stop checking exit codes passthu and just handle an exception. 

### Run a batch of shell commands.
Run several passthru commands and return true.
```php
CLI::batchPassThru(['sudo apt install php', 'sudo apt install composer']);
```

## Support.
Mainly just supporting my own use at the moment updating this project in my spare time.

At some point higher PHP versions will be required but 
I do intend to try and support php7.2 and 7.4 separately for a while
even after moving to php8.1
Eg. If I add a new feature that will also work in 7.2 I'll add it as a minor version update to the old version still
allowing php7.2. (no guarantees tho).

## Feedback && Contributions welcome.
I am using the [MIT licence](LICENCE.md) so feel free to do what you want, however I do ask that you submit a PR if you make any improvments or fix any bugs.

If this project gives you a 1up🍄 and you just want to show some appreciation then,

[buy me a coffee. ☕😃 ![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=bradozman%40gmail.com&item_name=%E2%98%95+Turning+Coffee++into+Code.&currency_code=AUD)
