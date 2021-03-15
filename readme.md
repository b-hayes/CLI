# CLI
Turns a PHP class into a command line tool.
Public methods become commands executable by the user.
Parameters become arguments for the commands.
Scalar types and required params are enforced for you.
Doc blocks are displayed when --help option is used.

## Usage.
```php
(new \BHayes\CLI\CLI( $yourClass ));
```

### Example For wsl/linux/mac:
Save this file as an executable (chmod +x filename) in your bin path.
```php
#!/usr/bin/env php
<?php

declare(strict_types=1);//optional

require_once 'vendor/autoload.php'; //if installed via composer

//dynamically defined class with functions works but recommend making a real one.
$class = new class() {
    /**
    * This documentation appears if you use --help with tryMe 
    */
    public function tryMe(bool $bool, string $string, float $float, int $int){
        var_dump(func_get_args());
    }
};

new BHayes\CLI\CLI($class)->run();
```
