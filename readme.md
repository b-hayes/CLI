# CLI
Turn your PHP class into an interactive command line tool.

## Features
Any public method on your class can now run via terminal!
Typehint your method arguments and CLI will force the user to provide compatible inputs!
Automatic Usage messages derived from your code!
Automatic --help option using your doc blocks for self documenting code!
Easy interactive prompts and confirmation loops with default responses.

### Coming soon
Easy to use coloured output.
Config support for users to set/save custom defaults for your app.

## Installation
`composer install b-hayes/cli`

## Usage.
Simply define a class and inject it into CLI.

CLI will allow your public methods to be run by the terminal user and provide help for method arguments
and even enforce scalar typehints!

Make a file with a shebang line (#!) at the top like so:

```php
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

$yourClass = new Class() {
    function hello() {
        return 'Hi ' . \BHayes\CLI\CLI::prompt('Enter your name', `git config user.name`);
    }
};

(new \BHayes\CLI\CLI( $yourClass ))->run();
```

Make the file executable:
```chmod +x <filename>```

Now you can run it:
```
./<filename>
```

Now your class is command line application!

## Behaviours.
- Public methods of `$yourClass` become executable commands.
- Required arguments for public methods will be enforced.
- Scalar data types for arguments will be enforced.
- Prevents user from passing too many arguments unless method is explicitly variadic. (Php allows it but I dont.)
- Automatic usage telling the user how to use your application methods when they mess up.
- Help `--help` option will display your doc blocks if you have them.
- Anonymous classes work, but dynamically added functions do not. (intentional)
- Anything returned by a method is printed and no output is suppressed beforehand.
- If you do not inject your own class then CLI will run itself making its prompt methods available to the terminal.

### Errors and Exceptions
There is a set of custom UserResponse exceptions to assist and terminating the app with a message for the use.
All other errors and exceptions are for devs only and suppressed unless you run
the app in --debug mode.

CLI detects and adjusts the default php error reporting config to prevent errors spitting output to the
terminal twice.

### Responses to the user.
You can display messages to the user however you feel like it.
 - by printing yourself and exiting manually with an exit code if you wish.
 - by returning a string, array or anything else to print.
 - by throwing a UserResponse or extended exception type with the message, with desired colour, icon and exit code.
The UserResponse exceptions have some extended versions for error/success/warning responses
   so that you don't have to keep specifying the colours and icons and exit codes.

### Options/Flags
Based on the [POSIX](https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html)
standard CLI support short options `-o` and long options `--longOption`.

Options are mapped to public class variables defined in your class.
EG. If you want to have a --cats option for cats mode simply define the property
`public cats` and then simply check if its true.
```php
if($this->cats) { echo "Cat mode enabled!"; }
```

*Note CLI currently does not support options with arguments (planned for php7.4 and higher).*

#### Reserved Options.
CLI has reserved some options.
 - --help. Just prints related doc blocks and exits without running your class.
 - --debug. Debug mode, if enabled no exceptions/errors are suppressed.
   Your app can still use the debug option for its own purposes too.
 - -i does nothing but can not be used by your app.
   I have reserved it for "interactive mode" that I am thinking about building.

## Examples (wsl/linux/mac).

Save these examples as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`

### Example 1. Showcase of basic features.
Play with this to see a showcase of how type hinting dock blocks required and optional params
user prompts and different outputs etc are used.

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);//optional

use BHayes\CLI\UserResponse;

require_once 'vendor/autoload.php'; //if installed via composer

/**
 * This is the documentation that will appear when you type --help.
 */
class Example {

    /**
    * This one is easy to run.
    * try runMe with --help to see this text. 
    */
    public function runMe(string $optional = null) {
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
    public function tryMe(bool $bool, string $string, float $float, int $int) {
        return "You did it! You gave me bool a string, a float and an int.";
    }
    
    /**
     * This method will accept any number of string arguments while the
     * the others will fail if you pass them too many arguments.
     *
     * @param string ...$bunchOfStrings
    */
    public function variadic (string ...$bunchOfStrings){
        echo "You said ";
        if (empty($bunchOfStrings)) {echo "nothing.";}
        print_r($bunchOfStrings);
        echo "\n";
    }
    
    /**
    * Demos prompts.
    * 
    * @throws UserResponse
    */
    public function survey():string {
        if (! \BHayes\CLI\CLI::confirm('Shall we begin?')) {return "Cancelled";}
        $colour = \BHayes\CLI\CLI::prompt('Whats your favorite colour?');
        throw new UserResponse("I love $colour too!",$colour, '☺');
    }
    
    
    /**
    * Tests the UserResponse throwable. 
    *
    * @param bool|null $success
    * @throws UserResponse
    */
    public function throwsUserResponse(bool $success = null){
        if($success === true) {
            throw new \BHayes\CLI\UserSuccessResponse();//all params optional
        }
        if($success === false) {
            throw new \BHayes\CLI\UserErrorResponse('Some error message user needs to see!');
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
    public  function bar()
    {
        var_dump($this->foo);
    }
};

$cli = new BHayes\CLI\CLI(new Example());
$cli->run();
```

## Advanced options.
During development, you may wish to always run in debug mode without typing --debug all the time.
```php
$cli->debug = true;
```
For many reasons, you may prefer to throw your own custom exceptions
for messages the user should see in the terminal instead of relying on 
the CLI UserResponse exceptions. 

Simpy 