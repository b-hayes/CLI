<?php

namespace BHayes\CLI;

use ReflectionMethod;

/**
 * Class CLI
 *
 * @package BHayes\CLI
 */
class CLI
{
    /**
     * @var object
     */
    private $class;
    
    /**
     * @var \ReflectionClass
     */
    private $reflection;
    
    /**
     * @var array
     */
    private $argv;
    
    /**
     * @var string|null
     *
     * argument zero, usually the command typed into the terminal
     */
    private $command;
    
    /**
     * @var string|null
     */
    private $function;
    
    /**
     * @var array
     */
    private $options = [];
    
    /**
     * @var array
     */
    private $params = [];
    
    
    /**
     * CLI constructor.
     *
     * @param object|null $class if unspecified, $this is used.
     */
    public function __construct(object $class = null)
    {
        //set the class to interface with
        $this->class = $class ??  $this;
        //get a reflection of said class
        try {
            $this->reflection = new \ReflectionClass($this->class);
        } catch (\ReflectionException $e) {
            $this->error($e);
        }
    }
    
    public function run()
    {
        //[ PROCESSING ARGUMENTS ]
        global $argv;
        
        //remove argument 0 is often the first word the user typed (usually dont care about or use it for anything)
        $this->command = array_shift($argv);
        
        //if no arguments just skip all the processing and display usage
        if (empty($argv)) {
            $this->usage();
            exit(0);
        }
        
        //next should process all the flags
        /*
         * todo:  for flags perhaps check out some conventions:
         *  https://unix.stackexchange.com/questions/108058/common-flag-designations-and-standards-for-shell-scripts-and-functions
         *  https://unix.stackexchange.com/questions/285575/whats-the-difference-between-a-flag-an-option-and-an-argument
         *  https://www.math.uni-hamburg.de/doc/java/tutorial/essential/attributes/_posix.html
         *  https://en.wikipedia.org/wiki/POSIX
         *  This one is probably th eone to follow : https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
         */
        foreach ($argv as $key => $arg) {
            //check for --long-options first
            if (substr($arg, 0, 2) == "--") {
                $this->options[] = substr($arg, 2);
                unset($argv[$key]);
                continue;
            }
            
            //then for short options -o
            if (substr($arg, 0, 1) == "-") {
                $arg = substr($arg, 1); //remove dash
                //multiple options grouped together
                $this->options = array_merge($this->options, str_split($arg));
                unset($argv[$key]);
                continue;
            }
            //todo: options can have arguments eg, mysql -u username,
            // need to detect if an option needs a param.
        }
        
        //the very next argument should be the class method to call
        $this->function = array_shift($argv);
        
        //everything after that is a parameter for the function
        $this->params = $argv;
        
        //todo: would be nice to detect if the user has setup their own ini configs at run time in php.
        //cli often shows errors twice if you have both of them on because log goes to stdout
        ini_set('log_errors', 0);
        ini_set('display_errors', 1);
    
        
        if (in_array('--help', $this->options)) {
            $this->help();
            exit(0);
        }
        
        if (!$this->function) {
            echo "No function was specified.\n";
            $this->listAvailableFunctions();
            exit(1);
        }
        
        if (! method_exists($this->class, $this->function)) {
            echo "[",$this->function,"]", " is not a recognized function!\n";
            $this->listAvailableFunctions();
            exit(1);
        }
    
        $this->execute();
    }
    
    /**
     * Replicates the readline function form the readline php extension (so the php ext is no longer required)
     *
     * @param null $prompt
     * @return string
     */
    private function readline($prompt = null)
    {
        if ($prompt) {
            echo $prompt;
        }
        $fp = fopen("php://stdin", "r");
        return rtrim(fgets($fp, 1024));
    }
    
    /**
     * Prompts the user for keyboard input.
     *
     * @param string $message a prompt messages to display
     * @param string $default if set will show up in prompt
     * @param bool $lowercase if true returns input as lowercase
     * @return string
     */
    public function prompt(string $message = 'enter response>', string $default = '', bool $lowercase = true): string
    {
        if ($default) {
            $message .= "($default)";
        }
        $readline = self::readline($message);
        if (strlen($readline) === 0) {
            $readline = $default;
        }
        if (strtolower($readline === 'exit')) {
            exit();
        }
        if ($lowercase) {
            $readline = strtolower($readline);
        }
        return $readline;
    }
    
    private function begin()
    {
        echo "Options:";
        print_r($this->options);
        echo "Function: ",$this->function,"\n";
        echo "Params:";
        print_r($this->params);
    }
    
    /**
     * Display / log generic error message acording to php ini settings.
     *
     * @param \Throwable $e
     * @param null $userFriendlyMessage
     */
    private function error(\Throwable $e, $userFriendlyMessage = null)
    {
        if ($userFriendlyMessage) {
            echo $userFriendlyMessage, "\n";
        }
        
        //todo: possible elevate all errors to exceptions to handle ever possible scenario?
        if (ini_get('display_errors')) {
            echo get_class($e),": ";
            echo $e->getMessage(), "\n";
            print_r($e->getTraceAsString());
        }
        if (ini_get('log_errors')) {
            log($e->getMessage() . ' ' . $e->getTraceAsString());
        }
        
        //todo: check if there is a convention for fatal error codes in cli (follow bash/unix /posix standard?)
        exit(1);
    }
    
    /**
     * Display basic commandline use.
     */
    private function usage()
    {
        $usage = $this->class->usage
            ?? $this->reflection->getDocComment()
            ?? "usage: " . $this->command . "[function] [-?][operands...]";
        echo $usage, "\n";
        $this->listAvailableFunctions();
        echo "Use --help for more information or [function] --help for more specific help.\n";
    }
    
    private function listAvailableFunctions()
    {
        echo "Functions available:\n";
        foreach ($this->reflection->getMethods() as $class_method) {
            if ($class_method->getName() == '__construct') {
                continue;//construct is ignored
            }
            if (!$class_method->isPublic()) {
                continue;//only public methods are listed
            }
            echo "    - {$class_method->getName()}\n";
        }
    }
    
    private function help()
    {
        if (!$this->function) {
            $this->usage();
            exit(0);
        }
    }
    
    private function execute()
    {
        try {
            $reflectionMethod = new ReflectionMethod($this->class, $this->function);
            $result = $reflectionMethod->invoke($this->class, ...$this->params);
            print_r($result);
            echo "\n";
            exit(0);
        } catch (\Throwable $e) {
            $this->error($e);
        }
    }
}
