<?php

declare(strict_types=1);

namespace BHayes\CLI\Test;

/**
 * Class TestSubject
 *
 * This is just to test what methods and params on a class via CLI.
 *
 * @package BHayes\CLI\Test
 */
class TestSubject
{
    public $optionOne;
    public $optionTwo;

    public function simple()
    {
        echo __METHOD__ , " was executed!";
    }

    public function requiresTwo($required, $requiredAlso)
    {
        echo __METHOD__ , " was executed with params $required $requiredAlso";
    }

    public function requiredAndOptional($required, $optional = null)
    {
        echo __METHOD__ , " was executed with $required, $optional";
    }

    public function allOptional(string $optionalString = '', int $optionalInt = 5, object $optionalObject = null)
    {
        echo __METHOD__, " was executed!";
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
    }

    public function requiresBool(bool $mustBeBool)
    {
        echo __METHOD__, " was executed!";
    }

    public function requiresFloat(float $mustBeFloat)
    {
        echo __METHOD__, " was executed!";
    }

    public function throwsAnError()
    {
        throw new \Error(__METHOD__ . " hates you!");
    }

    public function typedVariadicFunction(int ...$amounts)
    {
        echo __METHOD__, " was executed!";
    }
    //TODO: test a method with the MIXED types when moving to php8
    //TODO: test a method with the UNION types when moving to php8

    public function binCheck(int $exitCode)
    {
        echo __METHOD__ . " was executed!";
        echo "\n";//because we are about to exit before cli can add the new line on the end of the output
        exit($exitCode);
    }

    /**
     * This method is used to test the help function.
     * It has a doc block that should be displayed to the user.
     */
    public function helpCheck()
    {
        echo __METHOD__, " was executed!";
    }

    public function noHelpCheck()// this one has no doc block to display
    {
        echo __METHOD__, " was executed!";
    }
}
