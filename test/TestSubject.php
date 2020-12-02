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
    public function simple()
    {
        echo __METHOD__ , " was executed";
        $func_get_args = func_get_args();
        if (count($func_get_args) > 0) {
            echo " with args:\n";
            print_r($func_get_args);
        }
    }

    public function requiresTwo($required, int $mustBeInt)
    {
        echo __METHOD__ , " was executed with params $required $mustBeInt";
    }

    public function requiredAndOptional($required, $optional = null)
    {
        echo __METHOD__ , " was executed";
        $func_get_args = func_get_args();
        if (count($func_get_args) > 0) {
            echo " with args:\n";
            print_r($func_get_args);
        }
    }
}
