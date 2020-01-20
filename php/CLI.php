<?php

namespace BHayes\CLI;

class CLI
{
    /**
     * @var object
     */
    private $class;


    /**
     * CLI constructor.
     * @param object|null $class if unspecified CLI will use itself as the subject
     */
    public function __construct(object $class = null)
    {
        $this->class = $class ??  $this;
        print_r($this->class);
    }
}
