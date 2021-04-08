#!/usr/bin/env php
<?php
require_once __DIR__ . '/_environment.php';

(new \BHayes\CLI\CLI(
    new class () {
        public function helloWorld()
        {
            echo __FUNCTION__, " was executed!";
        }

        //NOTE: these are for testing purposes only, I do not recommend using logic exception family for user responses!
        public function throwLogicException()
        {
            throw new \LogicException(__FUNCTION__ . " was executed!");
        }

        public function throwInvalidArgumentException()
        {
            throw new \InvalidArgumentException(__FUNCTION__ . " was executed!");
        }
    },
    //InvalidArgumentException extends LogicException so they both should be treated as use responses.
    [\LogicException::class]
))->run();
