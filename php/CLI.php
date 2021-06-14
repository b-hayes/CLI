<?php

declare(strict_types=1);

namespace BHayes\CLI;

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
     * @var array the remaining input arguments after the function is determined.
     */
    private $subjectArguments = [];

    /**
     * @var ReflectionMethod reflection of the method that will be executed with subjectArguments
     */
    private $reflectionMethod;

    /**
     * @var bool|null
     */
    private $debug = null;

    /**
     * @var string[]
     */
    private $clientMessageExceptions;

    /**
     * @var bool
     */
    private $help = false;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string
     */
    private $initiatorName;

    /**
     * @var string[]
     */
    private $bannedMethods = ['__construct', '__clone'];

    /**
     * CLI constructor.
     *
     * Creates a reflection of the class for introspective execution of its methods by the terminal user.
     * Detects and prevents error reporting config from showing errors more than once in terminal output.
     * Note: All errors/exceptions will be suppressed by default unless they are of the types
     *  specified in the clientMessageExceptions.
     *
     * If debug mode is enabled no exceptions/errors are suppressed.
     *
     * @param object|null $class                   if unspecified, $this is used.
     * @param string[]    $clientMessageExceptions list of custom exceptions to use for user responses.
     *
     * @throws Throwable only if debug mode is enabled.
     */
    public function __construct(object $class = null, array $clientMessageExceptions = [])
    {
        //copy argv
        global $argv;
        $this->arguments = $argv;

        //set the class to interface with
        $this->subjectClass = $class ?? $this;

        //get a reflection of said class
        $this->reflection = new ReflectionClass($this->subjectClass);

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
     * If the first argument does not match a public method name
     *  - a list of the public methods on the class is printed.
     *
     * If the minimum required parameters are not met OR there are too many arguments then:
     *  - the arguments for the method are listed.
     *
     * Options are matched to public properties defined int he subject class.
     *  Options are processed according to: https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
     *
     * RESERVED OPTIONS
     *  There are a number of reserved options that this CLI class uses such as the,
     *   --help option that will display additional information about any given method.
     *   --debug option for devs to see all errors and stack traces.
     *
     * @param bool|null $debug if true php errors/exceptions are thrown.
     *
     * @throws Throwable
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function run(bool $debug = null)
    {
        $this->debug = $debug;
        try {
            $this->prepare();
            return $this->execute();
        } catch (UserResponse $response) {
            $this->exitWith($response->message(), $response);
        } catch (Throwable $throwable) {
            //Is it a custom user response exception?
            foreach ($this->clientMessageExceptions as $customException) {
                if ($throwable instanceof $customException) {
                    $this->exitWith($throwable->getMessage(), $throwable);
                }
            }

            //its a real error
            $printMessage = "❌ Failed to execute '{$this->printableCommandName()}', the program crashed." .
                " Please contact the developers if this keeps happening.";
            $this->exitWith(
                $printMessage,
                $throwable
            );
        }
    }

    /**
     * Runs the method.
     *
     * @throws Throwable
     */
    private function execute()
    {
        try {
            $result = $this->subjectClass->{$this->subjectMethod}(...$this->subjectArguments) ?? '';
            if (is_string($result)) {
                echo $result;
            } else {
                echo json_encode($result, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
            }
            echo "\n";

            return $result;
        } catch (\TypeError $typeError) {
            //Important: is it a type error caused by bad user input?
            if (
                //todo: this dodgy hack should probably get replaced with a real type check in future.
                (
                    stripos($typeError->getMessage(), $this->subjectMethod) !== false &&
                    isset($typeError->getTrace()[1]) &&
                    $typeError->getTrace()[1]['file'] === __FILE__ &&
                    $typeError->getTrace()[1]['function'] === 'execute'
                )
                ||
                (
                    $this->subjectMethod === '__invoke' &&
                    strpos($typeError->getMessage(), '{closure}') &&
                    isset($typeError->getTrace()[2]) &&
                    $typeError->getTrace()[2]['file'] === __FILE__ &&
                    $typeError->getTrace()[2]['function'] === 'execute'
                )
            ) {
                //We caused the type error by trying to use the users input as a method argument,
                // so lets tell the user its their fault while stripping sensitive info out.
                $message = str_replace(
                    [
                        ' passed to ' . get_class($this->subjectClass) . "::$this->subjectMethod()",
                        ' passed to class@anonymous::__invoke()',
                        'passed to {closure}()',
                    ],
                    '',
                    $typeError->getMessage()
                );
                echo '❌ ' . explode(', ', $message)[0], ". ";
                $this->printHelp();
                exit(1);
            }

            throw $typeError;
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
     * @param string $message     a prompt messages to display
     * @param string $default     if set will display in brackets and be returned if the user presses enter only.
     * @param bool   $lowercase   if true returns input as lowercase
     * @param string $inputStream see readline doc block for more info.
     *
     * @return string
     */
    public static function prompt(
        string $message = 'enter response>',
        string $default = '',
        bool $lowercase = true,
        $inputStream = 'php://stdin'
    ): string {
        if (strlen($default)) {
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
    public static function confirm(
        string $message = 'Continue?',
        string $default = 'Y',
        $inputStream = 'php://stdin'
    ): bool {
        while (true) {
            $prompt = self::prompt($message, $default, true, $inputStream);
            switch ($prompt) {
                case 'y':
                case 'yes':
                    return true;
                case 'n':
                case 'no':
                    return false;
            }
        }
    }

    /**
     * Displays error message depending on input and debug settings.
     *
     * - prints $printMessage in red with an X emoji.
     * - if debug mode and there is an exception, the exception is thrown for error reporting.
     * - if throwable the code is used as an exit code, but not 0, if 0 exit code is 1.
     * - only UserResponse can use 0 as an exit code.
     *
     * @param string|null    $printMessage If null the exception message is used.
     * @param Throwable|null $throwable
     *
     * @throws Throwable
     */
    private function exitWith(string $printMessage, ?Throwable $throwable = null)
    {
        self::printLine($printMessage);

        if (!$throwable) {
            exit(1);
        }

        if ($throwable instanceof UserResponse) {
            //client response is allowed to exit with any code
            $exitCode = $throwable->getCode();
        } else {
            //use error code but prevent 0 as exit code
            $exitCode = $throwable->getCode() ?: 1;
        }

        if ($this->debug && $exitCode !== 0) {
            throw $throwable;
        }

        exit($exitCode);
    }

    private function printUsage()
    {
        $usage = "usage: " . $this->initiatorName . " [function] [-?][operands...]";
        echo $usage, "\n";
        $this->printAvailableCommands();
        echo "Use --help for more information.\n";
    }

    private function printAvailableCommands()
    {
        $reflectionMethods = $this->reflection->getMethods();
        if (empty($reflectionMethods)) {
            echo "{$this->reflection->getShortName()} has no commands for you to execute.\n";
            return;
        }
        echo "Commands available:\n";
        foreach ($reflectionMethods as $class_method) {
            //do not list any magic methods such as __construct or __toString
            if (substr($class_method->getName(), 0, 2) === '__') {
                continue;//construct is not listed
            }
            if (!$class_method->isPublic()) {
                continue;//only public methods are listed
            }
            if ($this->subjectClass instanceof self && $class_method->getName() === 'run') {
                continue;//dont list this method when you cant use it.
            }
            echo "    - {$class_method->getName()}\n";
        }
    }

    /**
     * Prints doc blocks or derives details from the class definition to guide the user.
     */
    private function printHelp()
    {
        if (!$this->reflectionMethod) {
            $doc = $this->reflection->getDocComment()
                ?: "No documentation found for {$this->printableAppName()}";
            $this->printFormattedDocs($doc);
            $this->printUsage();
            return;
        }

        $commandName = $this->printableCommandName();
        $doc = $this->reflectionMethod->getDocComment()
            ?: "No documentation found for $commandName";
        $this->printFormattedDocs($doc);
        $reflectionParameters = $this->reflectionMethod->getParameters();
        if (empty($reflectionParameters)) {
            echo "'$commandName' does not require any parameters.\n";
        } else {
            echo "'$commandName' has the following parameters:\n";
            foreach ($reflectionParameters as $reflectionParameter) {
                echo $reflectionParameter, "\n";
            }
        }
    }

    private function printableAppName(): string
    {
        static $name;
        if ($name) {
            return $name;
        }
        $name = $this->reflection->getShortName();
        //replace technical terms about invocable with the initiator name.
        if (strpos($name, 'class@anonymous') !== false || $name === 'Closure') {
            $name = $this->initiatorName;
        }

        return $name;
    }

    private function printableCommandName(): string
    {
        if (!$this->reflectionMethod) {
            return $this->printableAppName();
        }
        static $name;
        if ($name) {
            return $name;
        }
        $name = $this->reflectionMethod->getShortName();
        if ($name === '__invoke') {
            $name = $this->printableAppName();
        }

        return $name;
    }

    /**
     * Extracts the options form the command line arguments
     *
     * @throws Throwable
     * @throws UserWarningResponse
     */
    private function prepare()
    {
        //[ PROCESSING ARGUMENTS ]
        $args = $this->arguments;

        //remove argument 0 is the first word the user typed and only used for usage statement.
        $this->initiator = array_shift($args);
        $this->initiatorName = basename($this->initiator);

        //if the class itself is invokable than we inject the invoke as the method being called.
        if (is_callable($this->subjectClass)) {
            array_unshift($args, '__invoke');
        }

        //if no arguments just skip all the processing and display usage
        if (empty($args)) {
            $this->printUsage();
            exit(0);
        }

        //if there is only one argument and it is a help option then just show help now and exit (faster)
        if (count($args) === 1 && $args[0] === '--help') {
            $this->printHelp();
            exit(0);
        }

        /*
         * Process options/flags according to posix conventions:
         *  https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
         * Need to process these first and remove them from the remaining arguments.
         */
        $subjectProperties = get_class_vars(get_class($this->subjectClass));
        $cliReservedOptions = ['help', 'i'];
        if ($this->debug === null) {
            //if debug mode has not been set then allow it to be set with --debug option.
            $cliReservedOptions[] = 'debug';
        }

        foreach ($args as $key => $arg) {
            //LONG OPTIONS --example
            if (substr($arg, 0, 2) == "--") {
                $longOption = substr($arg, 2);

                //CLI Reserved options first
                if (in_array($longOption, $cliReservedOptions)) {
                    $this->{$longOption} = true;
                }

                if (array_key_exists($longOption, $subjectProperties)) {
                    $this->subjectClass->{$longOption} = true;
                } elseif (!in_array($longOption, $cliReservedOptions)) {
                    $this->exitWith("--$longOption is not a valid option.");
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
                        $this->exitWith("-$opt is not a supported option, yet.");
                    }

                    if (array_key_exists($opt, $subjectProperties)) {
                        $this->subjectClass->{$opt} = true;
                    } else {
                        $this->exitWith("-$opt is not a valid option.");
                    }
                }
                unset($args[$key]);
            }
        }

        //the very next argument should be the class method to call
        $this->subjectMethod = array_shift($args);
        if (!$this->subjectMethod) {
            echo "No function was specified.\n";
            $this->printAvailableCommands();
            exit(1);
        }
        if (in_array(strtolower($this->subjectMethod), $this->bannedMethods)) {
            $this->exitWith("'$this->subjectMethod' is not a recognized command.");
        }
        //prevent run command being run again causing a stack overflow.
        if ($this->subjectClass instanceof self && $this->subjectMethod === 'run') {
            $this->exitWith("yeah nah cant do that here sorry mate.");
        }

        //everything after that is a parameter for the function
        $this->subjectArguments = $args;

        //From here on other functions rely on the reflection method to exist.
        try {
            $this->reflectionMethod = new ReflectionMethod($this->subjectClass, $this->subjectMethod);
        } catch (ReflectionException $e) {
            //the method must not be defined in the class.
            $this->exitWith("'$this->subjectMethod' is not a recognized command.");
        }

        //method has to be public
        if (!$this->reflectionMethod->isPublic()) {
            $this->exitWith("'$this->subjectMethod' is not a recognized command.");
        }

        //help? should be executed before checking anything else.
        if ($this->help) {
            $this->printHelp();
            exit(0);
        }

        //intentionally prevent methods from being run with any number of arguments by default
        if (
            count($this->subjectArguments) > $this->reflectionMethod->getNumberOfParameters() &&
            $this->reflectionMethod->isVariadic() === false
        ) {
            $message = "Too many arguments! '" . $this->printableCommandName() .
                "' can only accept " . $this->reflectionMethod->getNumberOfParameters() .
                ' and you gave me ' . count($this->subjectArguments);

            if ($this->debug) {
                $message .= Colour::string("\nDebug note: ", Colour::AT_BOLD) .
                    'Php normally allows excess parameters but BHayes\CLI intentionally prevents this behaviour. ' .
                    ' You should consider using variadic functions if you need this.';
            }

            $this->exitWith($message);
        }

        //prevent too few arguments instead of catching Argument error.
        if (count($this->subjectArguments) < $this->reflectionMethod->getNumberOfRequiredParameters()) {
            echo "❌ Too few arguments. \n";
            $this->printHelp();
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
    }

    private function printFormattedDocs(string $doc)
    {
        $docLines = explode("\n", $doc);
        foreach ($docLines as $docLine) {
            if (strpos($docLine, '* @') !== false) {
                continue;
            }
            $docLine = str_replace('* Class ', '* ', $docLine);
            echo trim($docLine), "\n";
        }
    }

    /**
     * Prints text with with "\n" appended, with colour codes if supplied.
     *
     * @param string $text
     * @param int    ...$colours
     */
    public static function printLine($text = '', int ...$colours)
    {
        if ($colours) {
            $text = Colour::string($text, ...$colours);
        }
        echo $text, "\n";
    }

    public static function os(): string
    {
        $uname = php_uname();
        if (stripos($uname, 'linux') !== false) {
            if (strpos($uname, 'Microsoft') !== false) {
                return 'WSL';
            }
            if (strpos($uname, 'microsoft') !== false) {
                return 'WSL';//more specifically its probably WSL version two
            }
            return 'Linux';
        }

        if (stripos($uname, 'windows') !== false) {
            return 'Windows';
        }

        if (stripos($uname, 'darwin') !== false) {
            return 'MacOS';
        }

        return "UNKNOWN: $uname";
    }
}
