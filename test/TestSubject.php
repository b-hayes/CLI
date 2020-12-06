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
}
