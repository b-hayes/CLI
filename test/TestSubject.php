<?php

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
        echo __METHOD__ , " was executed";
        $func_get_args = func_get_args();
        if (count($func_get_args) > 0) {
            echo " with args:\n";
            print_r($func_get_args);
        }
    }

    public function requiresTwo($required, $requiredAlso)
    {
        echo __METHOD__ , " was executed with params $required $requiredAlso";
    }

    public function requiredAndOptional($required, $optional = null)
    {
        echo __METHOD__ , " was executed with $required, $optional";
    }

    private function shouldNotBeSeen()
    {
        echo __METHOD__ . " was executed!";
    }

    public function requiresBool(bool $mustBeBool)
    {
        echo __METHOD__, " was executed!";
    }

    public function requiresInt(int $mustBeInt)
    {
        echo __METHOD__, " was executed!";
    }

    public function requiresFloat(float $mustBeFloat)
    {
        echo __METHOD__, " was executed!";
    }

}
