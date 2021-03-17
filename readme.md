# CLI
Turn your PHP class into an interactive command line tool.

## Usage.
```php
(new \BHayes\CLI\CLI( $yourClass ))->run();
```
Simply define your class methods and cli can allow the user to run them.

## Behaviours.
- Public methods of `$yourClass` become executable commands.
- Required arguments for public methods will be enforced.
- Scalar data types for arguments will be enforced.
- Prevents use from passing too many arguments unless method is explicitly variadic.
- Automatic usage telling the user how to use your application methods when the mess up.
- Help `--help` option will display your doc blocks if you have them.
- Anonymous classes work, but dynamically added functions do not.
- Anything returned by a method is printed and no output is suppressed beforehand.
### Yet to be decided.
- should --options become properties of $yourClass?

## Examples.
For wsl/linux/mac.

Save these examples as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`

Example 1.
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
Example 2.
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
};

$cli = new BHayes\CLI\CLI(new Example());
$cli->run();
```
