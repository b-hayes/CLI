<?php

declare(strict_types=1);

namespace BHayes\CLI;

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
     * @var ReflectionClass this is a reflection of the subjectClass.
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
     * @var array the remaining input arguments after the function is determined.
     */
    private $subjectArguments = [];

    /**
     * @var ReflectionMethod reflection of the method that will be executed with subjectArguments
     */
    private $reflectionMethod;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $clientMessageExceptions;

    /**
     * @var array
     */
    private $reservedOptions = [];

    /**
     * @var bool
     */
    private $help = false;

    /**
     * @var array
     */
    private $arguments = [];


    /**
     * CLI constructor.
     *
     * Creates a reflection of the class for introspective execution of its methods by the terminal user.
     * Detects and prevents error reporting config from showing errors more than once in terminal output.
     * Note: All errors/exceptions will be suppressed by default regardless unless they are of the type
     *  specified in the clientMessageExceptions.
     *
     * If debug mode is enabled no exceptions/errors are suppressed.
     *
     * @param object|null $class if unspecified, $this is used.
     *
     * @throws Throwable only if debug mode is enabled.
     */
    public function __construct(object $class = null)
    {
        //copy argv
        global $argv;
        $this->arguments = $argv;

        //set the class to interface with
        $this->subjectClass = $class ?? $this;

        //get a reflection of said class
        try {
            $this->reflection = new ReflectionClass($this->subjectClass);
        } catch (ReflectionException $reflectionException) {
            $this->exitWith(
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
            ini_set('log_errors', '0');
        }

        /*
         * If an exception is thrown of this type it's message will be printed for the user.
         */
        $this->clientMessageExceptions = $clientMessageExceptions;
    }

    /**
     * Runs the class object with the arguments from terminal input:
     *  - The first argument is the name of the class method that will be run.
     *  - All remaining arguments will be passed on as parameters to the above method.
     *
     * IF the first argument does not match a public method name
     *  - a list of the public methods on the class is printed.
     *
     * If the minimum required parameters are not met OR there are too many arguments then:
     *  - the arguments for the method are listed.
     *
     * Options / Flags are recognizes as per the posix standard:
     *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
     *  todo Options are stored in for the class to decide if it shall use them or not.
     *
     * RESERVED OPTIONS
     *  There are a number of reserved options that this CLI class uses such as the,
     *   --help option that will display additional information about any given method.
     *   --debug option for devs to see all errors and stack traces.
     *
     * @throws Throwable
     */
    public function run()
    {
        //[ PROCESSING ARGUMENTS ]
        $args = $this->arguments;

        //remove argument 0 is the first word the user typed and only used for usage statement.
        $this->initiator = array_shift($args);

        //if no arguments just skip all the processing and display usage
        if (empty($args)) {
            $this->usage();
            exit(0);
        }

        //if there is only one argument and it is a help option then just show help now and exit (faster)
        if (count($args) === 1 && $args[0] === '--help') {
            $this->help();
            exit(0);
        }

        /*
         * Process options/flags according to posix conventions:
         *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
         * Need to process these first and remove them from the remaining arguments.
         */
        $subjectProperties = [];
        $subjectProperties = get_class_vars(get_class($this->subjectClass));
        $reservedOptions = ['debug','help', 'i'];

        foreach ($args as $key => $arg) {
            //LONG OPTIONS --example
            if (substr($arg, 0, 2) == "--") {
                $longOption = substr($arg, 2);

                //CLI Reserved options first
                if (in_array($longOption, $reservedOptions)) {
                    $this->{$longOption} = true;
                }

                if (array_key_exists($longOption, $subjectProperties)) {
                    $this->subjectClass->{$longOption} = true;
                } elseif (!in_array($longOption, $reservedOptions)) {
                    throw new UserWarningResponse("--$longOption is not a valid option.");
                }

                //remove the argument so its not used for a method.
                unset($args[$key]);
                continue;
            }

            //SHORT OPTIONS -abc
            if (substr($arg, 0, 1) == "-") {
                $arg = substr($arg, 1); //remove dash
                //multiple options can be grouped together eg -abc
                foreach (str_split($arg) as $opt) {
                    //CLI Reserved options
                    if ($opt === 'i') {
                        $this->exitWith(
                            "-i option is not supported.",
                            new \Exception('-i is reserved for an interactive mode I was hoping to build later.')
                        );
                    }

                    if (array_key_exists($opt, $subjectProperties)) {
                        $this->subjectClass->{$opt} = true;
                    } else {
                        throw new UserWarningResponse("-$opt is not a valid option.");
                    }
                }
                unset($args[$key]);
                continue;
            }
            //todo: support options with arguments eg. mysql -u username eg2. -files ...fileNames,
            // instead of just assigning 'true' value.
            /*
             * Note: thinking about the best way to achieve this would be to use typed properties from
             *  Php7.4 but i might do without this, releaser a php7.2 version first.
             *  Could then also enforce scalar types for the value options.
             */
        }

        //the very next argument should be the class method to call
        $this->subjectMethod = array_shift($args);
        if (!$this->subjectMethod) {
            //todo: might be good in future to allow for _invoke() when no method is specified
            // just in case there are those who just want the script to run without any arguments?
            echo "No function was specified.\n";
            $this->listAvailableFunctions();
            exit(1);
        }

        //everything after that is a parameter for the function
        $this->subjectArguments = $args;

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
        if ($this->help) {
            $this->help();
            exit(0);
        }

        //intentionally prevent all functions from being run with any number of arguments by default
        if (
            count($this->subjectArguments) > $this->reflectionMethod->getNumberOfParameters() &&
            $this->reflectionMethod->isVariadic() === false
        ) {
            echo "Too many arguments! '", $this->subjectMethod,
            "' can only accept ", $this->reflectionMethod->getNumberOfParameters(),
            ' and you gave me ', count($this->subjectArguments), "\n";
            if ($this->debug) {
                echo '❌ Php normally allows excess parameters but CLI is preventing this behaviour. ',
                ' You should consider using variadic functions if you need this.',
                "\n";
            }
            exit(1);
        }

        //prevent too few arguments instead of catching Argument error.
        if (count($this->subjectArguments) < $this->reflectionMethod->getNumberOfRequiredParameters()) {
            echo "❌ Too few arguments. \n";
            $this->help();
            exit(1);
        }

        //arguments must be able to pass strict scalar typing.
        foreach ($this->reflectionMethod->getParameters() as $pos => $reflectionParameter) {
            if (!array_key_exists($pos, $this->subjectArguments)) {
                //we have no more arguments to convert.
                break;
            }

            $reflectionType = $reflectionParameter->getType();
            //Note: supporting php versions with deprecated string casts.
            // See https://www.php.net/manual/en/reflectiontype.tostring.php
            if ($reflectionType instanceof \ReflectionNamedType) {
                $reflectionType = $reflectionType->getName();
            } elseif (is_object($reflectionType) && get_class($reflectionType) === '\ReflectionUnionType') {
                throw new \Exception("Union parameter types (and PHPv8 in general) is not yet supported.");
            } else {
                //older PHP versions can cast to a string.
                $reflectionType = (string)$reflectionType;
            }

            //convert the input if needed...
            if (empty($reflectionType) || $reflectionType === 'string' || $reflectionType === 'mixed') {
                continue;//no conversion needed.
            }
            if ($reflectionType !== 'string') {
                if ($this->reflectionMethod->isVariadic()) {
                    //all remaining params will also be the same type.
                    while ($pos < count($this->subjectArguments)) {
                        $this->subjectArguments[$pos] = json_decode($this->subjectArguments[$pos]);
                        $pos++;
                    }
                } else {
                    $this->subjectArguments[$pos] = json_decode($this->subjectArguments[$pos]);
                }
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
        } catch (UserResponse $response) {
            //todo: check what happens in debug mode when there is a previous throwable attached.
            $this->exitWith($response->message(), $response);
        } catch (Throwable $throwable) {
            //Is it a type error caused by bad user input?
            if (
                $throwable instanceof \TypeError &&
                strpos($throwable->getMessage(), $this->subjectMethod) !== false &&
                isset($throwable->getTrace()[1]) &&
                $throwable->getTrace()[1]['file'] === __FILE__ &&
                $throwable->getTrace()[1]['function'] === __FUNCTION__
            ) {
                //We caused the type error by trying to use the users input as a method argument,
                // so lets tell the suer its their fault.
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
            $this->exitWith(
                "Failed to execute '{$this->subjectMethod}', the program crashed." .
                " Please contact the developers if this keeps happening.",
                $throwable
            );
        }
    }

    /**
     * Reads a single line from standard input.
     * Similar to standard readline function without the need for the php extension.
     * Note:
     *  If you pass in a string then a resource handle is created and discarded after reading the first line.
     *  Pass in your own resource handle if you want to consecutively read the next line of a single source.
     *
     * @param string $prompt      a prompt messages to display
     * @param string $inputStream 'php://stdin' | 'data://text/plain,<your text>' | 'file://<path>' | <resource handle>
     *
     * @return string
     */
    public static function readline(string $prompt = '', $inputStream = 'php://stdin'): string
    {
        if ($prompt) {
            echo $prompt;
        }

        if (is_resource($inputStream)) {
            return trim(fgets($inputStream, 1024));
        }

        $handle = fopen($inputStream, "r");
        $input = trim(fgets($handle, 1024));
        fclose($handle);
        return $input;
    }

    /**
     * Prompts the user for keyboard input.
     *
     * @param string $message       a prompt messages to display
     * @param string $default       if set will display in brackets and be returned if the user presses enter only.
     * @param bool   $lowercase     if true returns input as lowercase
     * @param string $inputStream   see readline doc block for more info.
     *
     * @return string
     */
    public static function prompt(
        string $message = 'enter response>',
        string $default = '',
        bool $lowercase = true,
        $inputStream = 'php://stdin'
    ): string {
        if ($default) {
            $message .= "[$default]";
        }
        $readline = self::readline($message, $inputStream);
        if (strlen($readline) === 0) {
            $readline = $default;
        }
        if ($lowercase) {
            $readline = strtolower($readline);
        }
        return $readline;
    }

    /**
     * Prompts the user for confirmation and returns true or false.
     *  Matches true with Y, YES and OK
     *  Matches false with N, NO
     *  Matches are not case sensitive.
     *  If input matches nothing the user is prompted again.
     *
     * @param string $message message to display to the user
     * @param string $default default is 'Y' unless you change it. This wont change the true/false matching.
     * @param string $inputStream
     *
     * @return bool
     */
    public static function confirm(string $message = 'Continue?', string $default = 'Y', $inputStream = 'php://stdin'): bool
    {
        while (true) {
            $prompt = self::prompt($message, $default, true, $inputStream);
            switch ($prompt) {
                case 'y':
                case 'yes':
                    return true;
                    break;
                case 'n':
                case 'no':
                    return false;
                break;
            }
        }
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
    private function exitWith(string $printMessage, Throwable $throwable)
    {
        echo $printMessage, "\n";//todo: add some colour with a special print function?

        if ($this->debug) {
            throw $throwable;
        }

        //user response is allowed to exit with any code
        if ($throwable instanceof UserResponse) {
            exit($throwable->getCode());
        }

        //allow exit code from exception but never exit with 0.
        exit($throwable->getCode() ?: 1);
    }

    private function usage()
    {
        $usage = "usage: " . $this->initiator . " [function] [-?][operands...]";
        echo $usage, "\n";
        $this->listAvailableFunctions();
        echo "Use --help for more information.\n";
    }

    private function listAvailableFunctions()
    {
        $reflectionMethods = $this->reflection->getMethods();
        if (empty($reflectionMethods)) {
            echo "{$this->reflection->getShortName()} as no functions for you to execute.\n";
            return;
        }
        echo "Functions available:\n";
        foreach ($reflectionMethods as $class_method) {
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
            $doc = $this->reflection->getDocComment()
                ?: "No documentation found for {$this->reflection->getShortName()}";
            echo $doc, "\n";
            $this->usage();
            return;
        }

        $shortName = $this->reflectionMethod->getShortName();
        $doc = $this->reflectionMethod->getDocComment()
            ?: "No documentation found for {$shortName}";
        //strip out tab indents
        $doc = str_replace("\n    ", "\n", $doc);
        echo $doc, "\n";

        $reflectionParameters = $this->reflectionMethod->getParameters();
        if (empty($reflectionParameters)) {
            echo "'{$shortName}' does not require any parameters.\n";
        } else {
            echo "'{$shortName}' has the following parameters:\n";
            foreach ($reflectionParameters as $reflectionParameter) {
                echo $reflectionParameter, "\n";
            }
        }
    }

    public function enableDebugMode()
    {
        $this->debug = true;
    }
}
