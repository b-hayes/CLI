# CLI
Turn your PHP class into an interactive command line tool.

## Features
Any public method on your class can be run via terminal user!
Typehint your method arguments and CLI will force the user to provide compatible inputs!
Automatic Usage messages derived from your code!
Automatic --help option using your doc blocks for self documenting code!

## Installation
composer install b-hayes/cli

## Usage.
Simply define class methods how you want and inject it into CLI.

Make a file with a shebang line (#!) at the top like so:

```php
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

(new \BHayes\CLI\CLI( $yourClass ))->run();
```

Make the file executable:
```sudo chmod +x <filename>```

Now you can run it:
```
./<filename>
```

CLI will guide the user how to use your class as a command line application!

## Behaviours.
- Public methods of `$yourClass` become executable commands.
- Required arguments for public methods will be enforced.
- Scalar data types for arguments will be enforced.
- Prevents user from passing too many arguments unless method is explicitly variadic. (Php allows it but I dont.)
- Automatic usage telling the user how to use your application methods when they mess up.
- Help `--help` option will display your doc blocks if you have them.
- Anonymous classes work, but dynamically added functions do not. (intentional)
- Anything returned by a method is printed and no output is suppressed beforehand.

### Errors and Exceptions
All errors and exceptions are suppressed unless you run with --debug mode and then all errors are thrown.
Automatically detects and adjusts the default php error reporting config to prevent the duplicate output
 of uncaught errors in the terminal.

There is a new custom exception called UserResponse that CLI will catch and print its messages for the user.

### Responses to the user.
You can display messages to the user however you feel like it.
 - by printing yourself and exiting manually with an exit code if you wish.
 - by returning a string, array or anything else to print.
 - by throwing a UserResponse or extended exception type with the message, with desired colour, icon and exit code.

### Yet to be decided.
- should --options become properties of $yourClass for easy reference? eg. `if (this->optionOne)`
- reformatting of how dock blocks and method param lists are displayed for help and usage. 

## Examples (wsl/linux/mac).
Dynamic classes can be used for quickly writing a cli application in a self-contained file.

Save these examples as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`

### Example 1.
```php
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

(new BHayes\CLI\CLI(new class(){
    function hello(){
        return 'hello ' . `git config user.name` . ' your amazing!';
    }
}))->run();
```
### Example 2.
PLay with this to see how the type hinting dock blocks required and optional params etc are used.

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);//optional

require_once 'vendor/autoload.php'; //if installed via composer

/**
 * This is the documentation that will appear when you type --help.
 */
class Example {

    /**
    * This one is easy to run.
    * try runMe with --help to see this text. 
    */
    public function runMe(string $anything = null) {
        //either return output or just output directly its up to you.
        echo "I work with no arguments.";
        if ($anything !== null) {
            echo " But thanks for providing me with: ";
            var_dump($anything);
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
        if (empty($bunchOfStrings)) echo "nothing.";
        print_r($bunchOfStrings);
        echo "\n";
    }
    
    /**
    * Demos prompts.
    * 
    * @throws \BHayes\CLI\UserResponse
    */
    public function survey():string {
        if (! \BHayes\CLI\CLI::confirm('Shall we begin?')) return "Cancelled";
        $colour = \BHayes\CLI\CLI::prompt('Whats your favorite colour?');
        throw new \BHayes\CLI\UserResponse("I love $colour too!",$colour, '☺');
    }
    
    
    /**
    * Tests the new UserResponse throwables. 
    *
    * @param bool|null $success
    * @throws \BHayes\CLI\UserResponse
    */
    public function throwsUserResponse(bool $success = null){
        if($success === true) {
            throw new \BHayes\CLI\UserSuccessResponse();//all params optional
        }
        if($success === false) {
            throw new \BHayes\CLI\UserErrorResponse('Some error message user needs to see!');
        }
        throw new \BHayes\CLI\UserResponse('Try this again with true or false.');
    }
};

$cli = new BHayes\CLI\CLI(new Example());
$cli->run();
```
