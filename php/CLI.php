<?php

namespace BHayes\CLI;

use ArgumentCountError;
use ReflectionMethod;
use Throwable;

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
     * @var ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @var bool
     */
    private $debug;


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
            $this->error($e, $e->getMessage());
        }
        
        
        /*
         * We only want to see errors once in terminal window.
         *
         * In the default php cli installation both display and log errors are directed to stdout.
         * So we disable the log errors but only if both are enabled and no log file was configured.
         * IF the user has setup their own error log than we dont want to touch anything.
         */
        if (
            ini_get('log_errors') && //but only if both are enabled
            ini_get('display_errors') &&
            ini_get('error_log') === ""//and only if they haven't configured a log file
        ) {
            ini_set('log_errors', 0);
        }
        //todo: load config from json file if one is specified
    }

    public function run()
    {
        //[ PROCESSING ARGUMENTS ]
        global $argv;

        //remove argument 0 is the first word the user typed and only used for usage statement.
        $this->command = array_shift($argv);

        //if no arguments just skip all the processing and display usage
        if (empty($argv)) {
            $this->usage();
            exit(0);
        }
        
        //if there is only one argument and it is a help option then just show help and exit
        if (count($argv) === 1 && $argv[0] === '--help') {
            $this->help();
            exit(0);
        }

        /*
         * TODO: Process flags according to:
         *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
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
            // need to detect if an option requires a param.
            /*
             * Note: thinking about the best way to achieve this would be to have the datatype specified in,
             * an options class or as public class properties on the subject itself.
             * Php7.4 property syntax would be the best way but it's not common enough yet to rely on.
             */
        }

        //the very next argument should be the class method to call
        $this->function = array_shift($argv);

        //everything after that is a parameter for the function
        $this->params = $argv;

        //if we cant get a reflection then the method does not exist
        try {
            $this->reflectionMethod = new ReflectionMethod($this->class, $this->function);
        } catch (\ReflectionException $e) {
            $this->error($e, "[" . $this->function . "]" . " is not a recognized command.");
        }

        //intentionally prevent all functions from being run with any number of arguments by default
        if (count($this->params) > $this->reflectionMethod->getNumberOfParameters()) {
            $errorMessage = 'Too many arguments. Function ' . $this->reflectionMethod->getName() . ' can only accept ' .
                $this->reflectionMethod->getNumberOfParameters();
            $this->error(new \Exception($errorMessage . ' see line ' . __LINE__), $errorMessage);
        }


        //CLI RESERVED OPTIONS
        //debug messages from CLI class
        if (in_array('debug', $this->options)) {
            $this->debug = true;
        }
        //help?
        if (in_array('help', $this->options)) {
            $this->help();
            exit(0);
        }

        if (!$this->function) {
            echo "No function was specified.\n";
            $this->listAvailableFunctions();
            exit(1);
        }

        $this->execute();
    }

    private function execute()
    {
        if (! method_exists($this->class, $this->function)) {
            echo "[",$this->function,"]", " is not a recognized function!\n";
            $this->listAvailableFunctions();
            exit(1);
        }

        try {
            $reflectionMethod = new ReflectionMethod($this->class, $this->function);
            if (!$reflectionMethod->isPublic()) {
                //method has to be public
                echo "[",$this->function,"]", " is not a recognized function!\n";
                $this->listAvailableFunctions();
                exit(1);
            }

            $result = $reflectionMethod->invoke($this->class, ...$this->params);
            print_r($result);
            echo "\n";
            exit(0);
        } catch (ArgumentCountError $argumentCountError) {
            $message = str_replace(['()', get_class($this->class), '::'], "", $argumentCountError->getMessage());
            $this->error($argumentCountError, $message);
        } catch (Throwable $e) {
            $this->error($e);
        }
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
    
    /**
     * Display error message depending on input and debug settings.
     * - always outputs $printMessage.
     * - if debug mode is on then also prints the exception message.
     * - if logs are enabled in php ini a log entry is also created
     *
     * @param Throwable $e
     * @param string $printMessage
     */
    private function error(Throwable $e, string $printMessage)
    {
        if ($printMessage) {
            echo $printMessage, "\n";
        }

        //todo: possibly elevate all errors to exceptions to handle every possible scenario

        if ($this->debug) {
            echo "\n";//todo maybe have some colour ?
            echo get_class($e),": ";
            echo $e->getMessage(), "\n";
            print_r($e->getTraceAsString());
            echo "\n";
        }
        if (ini_get('log_errors')) {
            log('CLI Internal Error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
        }

        exit(1);
    }

    /**
     * Display basic commandline use.
     */
    private function usage()
    {
        $usage =
            $this->class->usage
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
                continue;//construct is not listed
            }
            if (!$class_method->isPublic()) {
                continue;//only public methods are listed
            }
            echo "    - {$class_method->getName()}\n";
        }
    }

    private function help()
    {
        if (!$this->reflectionMethod) {
            $this->usage();
            exit(0);
        } else {
            echo $this->reflectionMethod->getDocComment()
//                ?: $this->reflectionMethod->__toString() //not useful unless there params and exposes class names
                ?: "No documentation found for [{$this->reflectionMethod->getName()}] \n";
        }
    }
}
