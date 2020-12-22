<?php

declare(strict_types=1);

namespace BHayes\CLI;

use ArgumentCountError;
use Exception;
use phpDocumentor\Reflection\Types\This;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

//TODO: commit a php5.6, 7.0, and 7.2 version? before adding more features and only support 7.4+?

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
     * This is the class that CLI will be allowing the user to interact with.
     *
     * @var object
     */
    private $subjectClass;

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
    private $subjectMethod;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $subjectArguments = [];

    /**
     * @var ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @var bool
     */
    private $debug = false;

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
     *
     * @throws Throwable
     */
    public function __construct(object $class = null)
    {
        //set the class to interface with
        $this->subjectClass = $class ?? $this;

        //get a reflection of said class
        try {
            $this->reflection = new ReflectionClass($this->subjectClass);
        } catch (ReflectionException $reflectionException) {
            $this->exitWithError(
                "Command Line Interface failed to initialize.",
                $reflectionException
            );
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
     *  - todo the arguments for the method are listed.
     *
     * Options / Flags are recognizes as per the posix standard:
     *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
     *  todo Options are stored in for the class to decide if it shall use them or not.
     *
     * RESERVED OPTIONS
     *  There are a number of reserved options that this CLI class uses such as the,
     *   --help option that will display additional information about any given method.
     *   --debug option for those developing a cli application using this as the executor
     *      todo displays additional information from internal errors
     *       and sometimes provides advice for CLI usage when it refuses to execute a command etc.
     *
     * @throws Throwable
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

        //if there is only one argument and it is a help option then just show help now and exit (faster)
        if (count($argv) === 1 && $argv[0] === '--help') {
            $this->help();
            exit(0);
        }

        /*
         * Process options/flags according to posix conventions:
         *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
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
            //todo: support options with arguments eg. mysql -u username eg2. -files ...fileNames.
            /*
             * Note: thinking about the best way to achieve this would be to use typed properties from
             *  Php7.4 but i might do without this, releaser a php7.2 version first.
             */
        }

        //CLI RESERVED OPTIONS
        //debug messages from CLI class
        if (in_array('debug', $this->options)) {
            $this->debug = true;
        }

        //the very next argument should be the class method to call
        $this->subjectMethod = array_shift($argv);
        if (!$this->subjectMethod) {
            //todo: might be good in future to allow for _invoke() when no method is specified
            // just in case there are those who just want the script to run without any arguments?
            echo "No function was specified.\n";
            $this->listAvailableFunctions();
            exit(1);
        }

        //everything after that is a parameter for the function
        $this->subjectArguments = $argv;

        //From here on other functions rely on the reflection method to exist.
        try {
            $this->reflectionMethod = new ReflectionMethod($this->subjectClass, $this->subjectMethod);
        } catch (ReflectionException $e) {
            //if we cant get a reflection then the method does not exist
            echo "'{$this->subjectMethod}' is not a recognized command!\n";
            $this->listAvailableFunctions();
            exit(1);
        }

        //method has to be public
        if (!$this->reflectionMethod->isPublic()) {
            echo "'{$this->subjectMethod}' is not a recognized command!\n";
            $this->listAvailableFunctions();
            if ($this->debug) {
                echo "❌ Only public methods can be executed. Make your methods public.\n";
            }
            exit(1);
        }

        //help? should be executed before checking anything else.
        if (in_array('help', $this->options)) {
            $this->help();
            exit(0);
        }

        //intentionally prevent all functions from being run with any number of arguments by default
        if (count($this->subjectArguments) > $this->reflectionMethod->getNumberOfParameters()) {
            echo "Too many arguments! '", $this->subjectMethod,
            "' can only accept ", $this->reflectionMethod->getNumberOfParameters(),
            ' and you gave me ', count($this->subjectArguments), "\n";
            if ($this->debug) {
                echo '❌ Php normally allows excess parameters but CLI is preventing this behaviour. ',
                ' You should consider using variadic parameters instead of relying on func_get_args.',
                "\n";
            }
            exit(1);
        }

        //prevent too few arguments instead of catching Argument error.
        if (count($this->subjectArguments) < $this->reflectionMethod->getNumberOfRequiredParameters()) {
            echo "❌ Too few arguments. ";
            $this->help();
            exit(1);
        }

        //arguments must be able to pass strict scalar typing.
        foreach ($this->reflectionMethod->getParameters() as $pos => $reflectionParameter) {
            $reflectionType = $reflectionParameter->getType();
            if (empty($reflectionType) || $reflectionType === 'string' || $reflectionType === 'mixed') {
                continue;//no conversion needed.
            }
            if ($reflectionType !== 'string') {
                $this->subjectArguments[$pos] = json_decode($this->subjectArguments[$pos]);
            }
        }

        $this->execute();
    }

    /**
     * Runs the method.
     *
     * @throws Throwable
     */
    private function execute()
    {
        try {
            $result = $this->subjectClass->{$this->subjectMethod}(...$this->subjectArguments);
            print_r($result);
            echo "\n";
            exit(0);
        } catch (Throwable $throwable) {
            //Is it an error caused user input?
            if (
                $throwable instanceof \TypeError &&
                strpos($throwable->getMessage(), $this->subjectMethod) !== false &&
                isset($throwable->getTrace()[1]) &&
                $throwable->getTrace()[1]['file'] === __FILE__ &&
                $throwable->getTrace()[1]['function'] === __FUNCTION__
            ) {
                //We caused the type error based on the users input so lets tell them its their fault.
                $message = str_replace(
                    ' passed to ' . get_class($this->subjectClass) . "::{$this->subjectMethod}()",
                    '',
                    $throwable->getMessage()
                );
                echo '❌ ' . explode(', ', $message)[0], ". ";
                $this->help();
                exit(1);
            }

            //its a real error
            $this->exitWithError(
                "Failed to execute '{$this->subjectMethod}', the program crashed." .
                " Please contact the developers if this keeps happening.",
                $throwable
            );
        }
    }

    /**
     * Replicates the readline function form the readline php extension (so the php ext is no longer required)
     *
     * @param null $prompt
     *
     * @return string
     */
    private function readline($prompt = null): string
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
     * @param string $message   a prompt messages to display
     * @param string $default   if set will show up in prompt
     * @param bool   $lowercase if true returns input as lowercase
     *
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
        if (strtolower($readline) === 'exit') {
            exit();
        }
        if ($lowercase) {
            $readline = strtolower($readline);
        }
        return $readline;
    }

    /**
     * Displays error message depending on input and debug settings.
     *
     * - always outputs $printMessage.
     * - always ensures output ends with a new line.
     * - If an error/exception is provided:
     *      - if debug mode is on, then the internal error message is displayed.
     *      - if logs are enabled in php ini the internal error is logged.
     *
     * @param string|null    $printMessage If null the exception message is used.
     * @param Throwable|null $throwable
     *
     * @throws Throwable
     */
    private function exitWithError(string $printMessage, Throwable $throwable)
    {
        echo $printMessage, "\n";//todo: add some colour with a special print function?

        if ($this->debug) {
            throw $throwable;
        }

        //allow exit code from exception but never exit with 0.
        exit($throwable->getCode() ?: 1);
    }

    private function usage()
    {
        $usage =
            $this->subjectClass->usage
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

    /**
     * Prints doc blocks or derives details from the class definition to guide the user.
     */
    private function help()
    {
        if (!$this->reflectionMethod) {
            $this->usage();
            return;
        }

        if ($this->reflectionMethod->getDocComment()) {
            echo $this->reflectionMethod->getDocComment(), "\n";
        }

        echo "'{$this->subjectMethod}' has the following parameters:\n";
        foreach ($this->reflectionMethod->getParameters() as $reflectionParameter) {
            echo $reflectionParameter, "\n";
        }
    }
}
