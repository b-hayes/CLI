<?php

declare(strict_types=1);

require_once __DIR__ . '/_environment.php';

function red(string $text)
{
    return "\033[" . \BHayes\CLI\Colour::RED . "m$text\033[0m";
}

function bold(string $text)
{
    return "\033[" . \BHayes\CLI\Colour::BOLD . "m$text\033[0m";
}

echo red("This should be red\n");
echo bold("This should be bold\n");
echo red(bold("This should be red and bold\n"));
echo bold(red("This should be bold and red\n"));
echo "\033[1;31mThis is what red and bold looks like manually\n\033[0m";
