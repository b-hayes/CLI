<?php

namespace BHayes\CLI;

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
            $this->handleException($e);
        }
    }
    
    public function run(): int
    {
        //get arguments form cli terminal
        global $argv;
        //remove argument 0
        $this->command = array_shift($argv);
        $this->argv = $argv;
        
        //todo: here is where you need to strip out all the --flags so they dont affect function name and params etc
        // ** also remember that a -- for most applications means the end of the params for this command
        // and subsequent args should be passed along.
        // (not sure how to handle this yet tho)
        
        //the very next argument should be the function to call
        $this->function = array_shift($argv);
        
        //everything after that is a parameter for the function
        
        
        //todo: would be nice to detect if the user has setup their own configs.
        //cli often shows errors twice if you have both of them on because log goes to stdout
        ini_set('log_errors', 0);
        ini_set('display_errors', 1);
    
        try {
            $this->begin();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
        
        
        return 0;
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
    public function prompt($message = '', $default = "", $lowercase = true)
    {
        $readline = self::readline($message . "\nenter response>");
        if (strlen($readline) === 0) {
            $readline = $default;
        }
        if (strtolower($readline === 'exit')) {
            exit();
        }
        if ($lowercase) {
            $readline = strtolower($readline);
        }
        echo "\n";
        return $readline;
    }
    
    private function begin()
    {
        if (empty($argv)) {
            $this->usage();
            exit(1);
        }
    }
    
    private function usage()
    {
        if (count($this->argv) > 0) {
            
            $class_method = new ReflectionMethod($runningClass, $function);
            $help = $class_method->getDocComment();
            echo "    ";//4 spaces coz docs are likely to be indented
            echo $help;
            echo "\navailable options:\n";
            listParams($class_method, $config);
        } else {
            //no more args get help for class object only GENERAL HELP
            $help = $reflection_class->getDocComment();
            echo $help;
            echo "\n";
            echo " available functions:\n";
            list_functions($reflection_class);
            echo "Note that default values for ANY function can be set in the config\n",
            "You can also use --config to interact with the config file instead of the code generator.";
        }
    }
    
    /**
     * Display / log generic error message acording to php ini settings.
     *
     * @param \Throwable $e
     */
    private function handleException(\Throwable $e)
    {
        //todo: possible elevate all errors to exceptions to handle ever possible senario?
        if (ini_get('display_errors')) {
            echo $e->getMessage(), "\n";
        }
        if (ini_get('log_errors')) {
            log($e->getMessage() . ' ' . $e->getTraceAsString());
        }
        //todo: check if there is a convention for fatal error codes in cli (follow bash/unix /posix standard?)
        exit(1);
    }
}
