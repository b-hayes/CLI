<?php

namespace BHayes\CLI;

class Options
{
    public function __construct(array $options = null)
    {
        foreach (getopt('f', ['help','list']) as $option => $value) {
            $this->{$option} = $value ?: true;
        }
    }

    /**
     * @var bool
     */
    public $debug;

    /**
     * @var bool
     */
    public $help;
}
