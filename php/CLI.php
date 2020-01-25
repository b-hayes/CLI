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
     * CLI constructor.
     *
     * @param object|null $class if unspecified CLI will use itself as the subject
     * @throws \ReflectionException
     */
    public function __construct(object $class = null)
    {
        $this->class = $class ??  $this;
        $this->reflection = new \ReflectionClass($class);
    }
    
    public function run(): int
    {
        //todo: would be nice to detect if the user has setup their own configs.
        //cli often shows errors twice if you have both of them on because log goes to stdout
        ini_set('log_errors', 0);
        ini_set('display_errors', 1);
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
}
