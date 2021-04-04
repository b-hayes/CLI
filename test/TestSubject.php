<?php

declare(strict_types=1);

namespace BHayes\CLI\Test;

use BHayes\CLI\UserErrorResponse;
use BHayes\CLI\UserResponse;
use BHayes\CLI\UserSuccessResponse;
use BHayes\CLI\UserWarningResponse;

/**
 * Class TestSubject
 *
 * This is just to test what methods and params on a class via CLI.
 *
 * @package BHayes\CLI\Test
 */
class TestSubject
{
    public $a;
    public $b;
    public $c;

    public $apple;
    public $banana;
    public $carrot;

    public $debug;

    public function simple()
    {
        echo __METHOD__ , " was executed!";
        var_dump(func_get_args());
    }

    public function requiresTwo($required, $requiredAlso)
    {
        echo __METHOD__ , " was executed with params $required $requiredAlso";
        var_dump(func_get_args());
    }

    public function requiredAndOptional($required, $optional = null)
    {
        echo __METHOD__ , " was executed with $required, $optional";
        var_dump(func_get_args());
    }

    public function allOptional(string $optionalString = '', int $optionalInt = 5, object $optionalObject = null)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    private function aPrivateMethod()
    {
        echo __METHOD__ . " was executed!";
    }

    protected function aProtectedMethod()
    {
        echo __METHOD__ . " was executed!";
    }

    public function requiresInt(int $mustBeInt)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function requiresBool(bool $mustBeBool)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function requiresFloat(float $mustBeFloat)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function throwsAnError()
    {
        throw new \Error(__METHOD__ . " hates you!");
    }

    public function typedVariadicFunction(int ...$amounts)
    {
        echo __METHOD__, " was executed!";
        var_dump(func_get_args());
    }

    public function binCheck(int $exitCode)
    {
        echo __METHOD__ . " was executed!";
        var_dump(func_get_args());
        echo "\n";//because we are about to exit before cli can add the new line on the end of the output
        exit($exitCode);
    }

    /**
     * This method is used to test the --help function.
     * It has a doc block that should be displayed to the user.
     *
     */
    public function helpCheck()
    {
        echo __METHOD__, " was executed!";
    }

    public function noHelpCheck()// this one has no doc block to display
    {
        echo __METHOD__, " was executed!";
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserResponse()
    {
        throw new UserResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserWarning()
    {
        throw new UserWarningResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserError()
    {
        throw new UserErrorResponse(__METHOD__ . ' says hi!');
    }

    /**
     * @throws UserResponse
     */
    public function throwsUserSuccess()
    {
        echo __METHOD__, " was executed!";
        throw new UserSuccessResponse();
    }

    public function checkOptions()
    {
        echo __METHOD__, " was executed!\n";
        foreach ($this as $property => $value) {
            echo $property,": ";
            var_dump($value);
        }
    }

    public function dumpGlobals(...$args)
    {
        echo __METHOD__, " was executed!";
        //the global arv should remain unmodified.
        global $argv;
        var_dump($argv);
    }
}
