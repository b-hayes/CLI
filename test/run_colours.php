<?php

declare(strict_types=1);

require_once __DIR__ . '/_environment.php';

if (php_sapi_name() === 'cli') {
    (new \BHayes\CLI\CLI(new \BHayes\CLI\Colour()))->run(true);
}



function red(string $text)
{
    return "\033[" . \BHayes\CLI\Colour::RED . "m$text\033[0m";
}

function bold(string $text)
{
    return "\033[" . \BHayes\CLI\Colour::BOLD . "m$text\033[0m";
}

function lightRed(string $text) {
    return "\033[" . \BHayes\CLI\Colour::LIGHT_RED . "m$text\033[0m";
}

echo red("This should be red\n");
echo bold("This should be bold\n");
echo red(bold("This should be red and bold\n"));
echo "\033[1;31mThis is what red and bold looks like manually\n\033[0m";
echo lightRed("This should be light red.\n");
echo bold(lightRed("This should be bold light red.\n"));
echo "\033[1;91mThis is what bold light is like manually\n\033[0m";
