<?php

declare(strict_types=1);

namespace BHayes\CLI;

use phpDocumentor\Reflection\Types\This;

class Colour
{
    public const DEFAULT = '39';
    public const BLACK = '30';
    public const RED = '31';
    public const GREEN = '32';
    public const YELLOW = '33';
    public const BLUE = '34';
    public const MAGENTA = '35';
    public const CYAN = '36';
    public const LIGHT_GREY = '37';

    public const DARK_GREY = '90';
    public const LIGHT_RED = '91';
    public const LIGHT_GREEN = '92';
    public const LIGHT_YELLOW = '93';
    public const LIGHT_BLUE = '94';
    public const LIGHT_MAGENTA = '95';
    public const LIGHT_CYAN = '96';
    public const WHITE = '97';

    public const BG_DEFAULT = '49';
    public const BG_BLACK = '40';
    public const BG_RED = '41';
    public const BG_GREEN = '42';
    public const BG_YELLOW = '43';
    public const BG_BLUE = '44';
    public const BG_MAGENTA = '45';
    public const BG_CYAN = '46';
    public const BG_LIGHT_GREY = '47';

    public const BG_DARK_GREY = '100';
    public const BG_LIGHT_RED = '101';
    public const BG_LIGHT_GREEN = '102';
    public const BG_LIGHT_YELLOW = '103';
    public const BG_LIGHT_BLUE = '104';
    public const BG_LIGHT_MAGENTA = '105';
    public const BG_LIGHT_CYAN = '106';
    public const BG_WHITE = '107';


    public const AT_BOLD = "1";
    public const AT_DIM = "2";
    public const AT_UNDERLINED = "4";
    public const AT_BLINK = "5";
    public const AT_INVERSE = "7";
    public const AT_HIDDEN = "8";

    public const RESET = "0";


    public static function getColourCodeFromName(string $colourName): string
    {
        static $constants;
        $constants = $constants ?? (new \ReflectionClass())->getConstants();
        var_export($constants);
    }

    public static function getColouredString(string $string, string $colour = null, $bgColour = null): string
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