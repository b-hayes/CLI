# CLI
Turn your PHP class into an interactive command line tool.
Create commandline tools in seconds simply by defining a class with some methods.

## Usage.
```php
(new \BHayes\CLI\CLI( $yourClass ))->run();
```

### Examples: (wsl/linux/mac).
Save these examples as a `testme` file and make it executable `chmod +x ./testme` and run with `./testme`
```php
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

(new BHayes\CLI\CLI(new class(){
    function hello(){
        return 'hello ' . `git config user.name` . 'Your amazing!';
    }
}))->run();
```


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
            echo " But thanks for providing me with:";
            var_dump($anything);
        }
    }
    
    /**
    * This command will only run when all the requirements are met.
    */
    public function tryMe(bool $bool, string $string, float $float, int $int) {
        echo "You did it! You gave me bool a string, a float and an int.";
    }
};

$cli = new BHayes\CLI\CLI(new Example());
$cli->run();
```
