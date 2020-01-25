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
     * @var array
     */
    private $options = [];
    
    /**
     * @var array
     */
    private $operands = [];
    
    
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
        //[ PROCESSING ARGUMENTS ]
        global $argv;
        //keep a copy of the original arguments jic
        $this->argv = $argv;
        
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
            }
            //then for options -o
            if (substr($arg, 0, 1) == "-") {
                $arg = substr($arg, 1); //remove dash
                //multiple options grouped together
                array_merge($this->options, str_split($arg));
                unset($argv[$key]);
            }
            //todo: options can have arguments eg, mysql -u username,
            // need to detect if an option needs a param.
        }
        
        //the very next argument should be the class method to call
        $this->function = array_shift($argv);
        
        //everything after that is a parameter for the function
        $this->operands = $argv;
        
        //todo: would be nice to detect if the user has setup their own ini configs at run time in php.
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
            $this->help();
            exit(1);
        }
    }
    
    private function help()
    {
        if (empty($this->function)){
            //no function was called display basic usage info
            $help = $this->reflection->getDocComment();
        }
        
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
    
    /**
     * Display basic commandline use.
     */
    private function usage()
    {
        echo get_class($this), "\n";
        echo "usage: ", $this->command,
        "[function] [-a][-b][-c option_argument][-d|-e][-f[option_argument]][operands...]\n";
        echo "use --help for more information or [function] --help for more specific help.\n";
    }
}
