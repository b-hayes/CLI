<?php
declare(strict_types=1);
namespace BHayes\CLI;

use ArgumentCountError;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * Class CLI
 *
 * @package BHayes\CLI
 *
 * CLI allows you to interact with php class objects from the terminal,
 * allowing you to write terminal application simply by defining the class methods and,
 * creating an executable wrapper file.
 *
 */
class CLI
{
    /**
     * @var object
     */
    private $class;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var string|null
     *
     * argument zero, usually the command typed into the terminal that initiated the execution of this script.
     */
    private $initiator;

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
     * @var string
     */
    public $inputStream = "php://stdin";


    /**
     * CLI constructor.
     *
     * Creates a reflection of the class for introspection.
     * Detects if the default error reporting and changes it to only show errors once in terminal output.
     *
     * TODO: loads a configuration file from ClassName.json file if it exists,
     *  the json file can contain default values for method arguments.
     *
     * @param object|null $class if unspecified, $this is used.
     */
    public function __construct(object $class = null)
    {
        //set the class to interface with
        $this->class = $class ??  $this;
        //get a reflection of said class
        try {
            $this->reflection = new ReflectionClass($this->class);
        } catch (ReflectionException $e) {
            $this->error($e, $e->getMessage());
        }


        /*
         * We only want to see errors once in terminal window.
         *
         * In the default php cli installation both display and log errors are directed to stdout.
         * So we disable the log errors but only if both are enabled and no log file was configured.
         * If the user has setup their own error log than we dont want to touch anything.
         */
        if (
            ini_get('log_errors') &&
            ini_get('display_errors') &&
            ini_get('error_log') === ""
        ) {
            ini_set('log_errors', 0);
        }

        //todo: load config from json file if one is specified
    }

    /**
     * Runs the class object with the arguments from terminal input:
     *  - The first argument is the name of the class method that will be run.
     *  - All remaining arguments will be passed on as parameters to the above method.
     *
     * IF the first argument does not match a public method name || no arguments are specified then:
     *  - a list of the public methods on the class is printed.
     *
     * If the minimum required parameters are not met OR there are too many arguments then:
     *  - the arguments for the method are listed.
     *
     * Options / Flags are recognizes as per the posix standard:
     *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
     *
     * Options are not passed along as method params.
     *  They are stored in memory for the class to decide if it shall use them or not.
     *
     * RESERVED OPTIONS
     *  There are a number of reserved options that this CLI class uses such as the,
     *   --help option that will display additional information about any given method.
     */
    public function run()
    {
        //[ PROCESSING ARGUMENTS ]
        global $argv;

        //remove argument 0 is the first word the user typed and only used for usage statement.
        $this->initiator = array_shift($argv);

        //if no arguments just skip all the processing and display usage
        if (empty($argv)) {
            $this->usage();
            exit(0);
        }

        //if there is only one argument and it is a help option then just show help and exit (faster)
        if (count($argv) === 1 && $argv[0] === '--help') {
            $this->help();
            exit(0);
        }

        /*
         * Process options/flags according to posix conventions:
         *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
         *
         * Note: why did I not use built in getopt() function?
         *      - support for "-x" is not a valid option. Options are:.....
         *      - options do not get removed from $argv so still have to manually go find and remove them
         *        before processing the remaining arguments as function parameters.
         *          -   could however have have if getopt(...) exits remove from arg v I guess but not optimal at all.
         *      - unable to have unlimited/unrestricted options in the case the
         *        subject class just wants to check it on the fly or dynamic use with this->optionAsProperty
         *      - getopt can not detect '--' empty option as passthroughs
         *        (a common convention when one command runs another)
         *        eg your class might be a wrapper for running phpunit with default options you like to always have on
         *        but allows the user to pass through additional options directly to phpunit themselves.
         *
         *  I did think perhaps to use getopt() to grab expect valid options and then check fo any left over ones,
         *  but then getopt doesnt remove any options you grab so you still need to manually check ever argv
         *  scenario to enable the feature of "-x is not a valid option"
         */
        foreach ($argv as $key => $arg) {
            //check for --long-options first
            if (substr($arg, 0, 2) == "--") {
                $this->options[] = substr($arg, 2);
                unset($argv[$key]);
                continue;
            }

            //then for short options -a
            if (substr($arg, 0, 1) == "-") {
                $arg = substr($arg, 1); //remove dash
                //multiple options can be grouped together eg -abc
                $this->options = array_merge($this->options, str_split($arg));
                unset($argv[$key]);
                continue;
            }
            //todo: options can have arguments eg, mysql -u username,
            // need to detect if an option requires a param. (could also use getopt function for this?)
            /*
             * Note: thinking about the best way to achieve this would be to use typed properties from
             *  Php7.4 but i might do without this, releaser a php7.2 version first.
             * TODO: commit a php-5.6 version then a 7.0 version before adding more features and only support 7.4+
             */
        }

        //the very next argument should be the class method to call
        $this->function = array_shift($argv);

        //everything after that is a parameter for the function
        $this->params = $argv;

        //HELP and other functions rely on the reflection to already exist.
        //if we cant get a reflection then the method does not exist
        try {
            $this->reflectionMethod = new ReflectionMethod($this->class, $this->function);
        } catch (ReflectionException $e) {
            echo "[" . $this->function . "]" . " is not a recognized command.\n";
            $this->listAvailableFunctions();
            exit(0);
        }

        //intentionally prevent all functions from being run with any number of arguments by default
        if (count($this->params) > $this->reflectionMethod->getNumberOfParameters()) {
            $errorMessage = 'Too many arguments. Function ' . $this->reflectionMethod->getName() . ' can only accept ' .
                $this->reflectionMethod->getNumberOfParameters();
            $this->error(new Exception($errorMessage . ' see line ' . __LINE__), $errorMessage);
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
        } catch (\TypeError $typeError) {
            $message = str_replace(['()', get_class($this->class), '::'], "", $typeError->getMessage());
            $this->error($typeError, $message);
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
        $handle = $handle ?? fopen($this->inputStream, "r");
        return rtrim(fgets($handle, 1024));
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
            $message .= " [$default]";
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
     * - always outputs $printMessage when specified.
     * - if debug mode is on then also prints the exception message.
     * - if logs are enabled in php ini a log entry is also created
     *
     * @param Throwable $e
     * @param string|null $printMessage If null the exception message is used.
     */
    private function error(Throwable $e, string $printMessage = null)
    {
        if ($printMessage) {
            //todo: add some colour with a print function
            //todo: is this even necessary? should exceptions just fall back to php reporting?
            echo $printMessage, "\n";

            //provide the ability to specify the exit code with an exception but dont let an error exit with 0.
            $code = $e->getCode();
            if ($code < 1) {
                $code = 1;
            }
            exit($code);
        }

        //todo: possibly elevate all errors to exceptions to handle every possible scenario
    }

    /**
     * Display basic commandline use.
     */
    private function usage()
    {
        $usage =
            $this->class->usage
            ?? $this->reflection->getDocComment()
            ?? "usage: " . $this->initiator . "[function] [-?][operands...]";
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

    private function runWithErrorHandling()
    {
        try {
            $this->run();
        } catch (\Exception $exception) {
            //it is generally assumed that any unhandled \Exception and alike is a general message for the user to see.
            //todo: should this be the case? or no?
            $this->exitWithErrorMessage($exception->getMessage(), $exception->getCode(), $exception);
        } catch (Throwable $throwable) {
            //These will usually be internal php errors that only developers should see.
            $this->exitWithErrorMessage("An unexpected error occurred contact developers for help.");
        }
    }

    /**
     * Used to handle exceptions left uncaught by the running subject.
     *  - The message is printed to the terminal.
     *  - The exception code is used as the exit code to allow devs to pass back specific codes form their class.
     *      Unless it is zero, then error code 1 is used to prevent false positive interpretations by the shell/user.
     *  - The original exception details are shown if --debug option was used.
     *
     * @param string         $userMessage
     * @param int            $errorCode
     * @param Throwable|null $throwable
     */
    private function exitWithErrorMessage(string $userMessage, int $errorCode = 1, Throwable $throwable = null)
    {
        //todo maybe have some colour for error? (perhaps implement a print function for easy colour output)
        echo $userMessage, "\n";

        if ($this->debug && $throwable) {
            echo "\n";
            echo get_class($throwable),": ";
            echo $throwable->getMessage(), "\n";
            print_r($throwable->getTraceAsString());
            echo "\n";
        } elseif (ini_get('log_errors')) {
            log('CLI Internal Error: ' . $throwable->getMessage() . ' ' . $throwable->getTraceAsString());
        }

        exit($errorCode ?: 1);
    }
}
