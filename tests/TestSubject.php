<?php


namespace BHayes\CLI\test;

/**
 * Class TestSubject
 *
 * This is just to test what methods and params on a class via CLI.
 *
 * @package BHayes\CLI\test
 */
class TestSubject
{
    function simple(){
        echo __METHOD__ , " was executed";
        $func_get_args = func_get_args();
        if (count($func_get_args) > 0) {
            echo " with args:\n";
            print_r($func_get_args);
        }
    }
    
    function requiresParams($required, int $mustBeInt){
        echo __METHOD__ , " was executed";
        $func_get_args = func_get_args();
        if (count($func_get_args) > 0) {
            echo " with args:\n";
            print_r($func_get_args);
        }
    }
}
