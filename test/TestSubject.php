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

    //todo: test the following classes next

    public function requiresInt(int $mustBeInt)
    {
        echo __METHOD__, " was executed!";
        var_dump($mustBeInt);
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
}
