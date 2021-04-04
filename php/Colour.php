<?php

declare(strict_types=1);

namespace BHayes\CLI;

use phpDocumentor\Reflection\Types\This;

class Colour
{
    public const BLACK = '0;30';
    public const DARK_GREY = '1;30';
    public const BLUE = '0;34';
    public const LIGHT_BLUE = '1;34';
    public const GREEN = '0;32';
    public const LIGHT_GREEN = '1;32';
    public const CYAN = '0;36';
    public const LIGHT_CYAN = '1;36';
    public const RED = '0;31';
    public const LIGHT_RED = '1;31';
    public const PURPLE = '0;35';
    public const LIGHT_PURPLE = '1;35';
    public const BROWN = '0;33';
    public const YELLOW = '1;33';
    public const LIGHT_GREY = '0;37';
    public const WHITE = '1;37';

    public const BG_BLACK = '40';
    public const BG_RED = '41';
    public const BG_GREEN = '42';
    public const BG_YELLOW = '43';
    public const BG_BLUE = '44';
    public const BG_MAGENTA = '45';
    public const BG_CYAN = '46';
    public const BG_LIGHT_GREY = '47';

    public const BOLD = "1";
    public const DIM = "2";
    public const UNDERLINED = "4";
    public const BLINK = "5";
    public const INVERSE = "7";
    public const HIDDEN = "8";
    public const RESET = "0";

    public const FG = [
        'BLACK' => '0;30',
        'DARK_GREY' => '1;30',
        'BLUE' => '0;34',
        'LIGHT_BLUE' => '1;34',
        'GREEN' => '0;32',
        'LIGHT_GREEN' => '1;32',
        'CYAN' => '0;36',
        'LIGHT_CYAN' => '1;36',
        'RED' => '0;31',
        'LIGHT_RED' => '1;31',
        'PURPLE' => '0;35',
        'LIGHT_PURPLE' => '1;35',
        'BROWN' => '0;33',
        'YELLOW' => '1;33',
        'LIGHT_GREY' => '0;37',
        'WHITE' => '1;37',
    ];

    public const BG = [
        'BLACK' => '40',
        'RED' => '41',
        'GREEN' => '42',
        'YELLOW' => '43',
        'BLUE' => '44',
        'MAGENTA' => '45',
        'CYAN' => '46',
        'LIGHT_GREY' => '47',
    ];


    private static function getColourCodeFromName(string $colourName): string
    {
        return self::FG[strtoupper($colourName)] ?? '';
    }

    private static function getColouredString(string $string, string $colour = null, $bgColour = null): string
    {
        if ($colour && $bgColour) {
            return "\033[{$colour};{$bgColour}m{$string}\033[0m";
        }

        if ($colour) {
            return "\033[{$colour}m{$string}\033[0m";
        }

        if ($bgColour) {
            return "\033[{$bgColour}m{$string}\033[0m";
        }

        return $string;
    }
}