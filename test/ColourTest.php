<?php

declare(strict_types=1);

namespace BHayes\CLI\Test;

use BHayes\CLI\Colour;
use PHPUnit\Framework\TestCase;

class ColourTest extends TestCase
{

    public function testString()
    {
        self::assertStringContainsString(
            "\033[" . Colour::YELLOW . "m",
            Colour::string('Hello', Colour::YELLOW)
        );
    }

    public function testCode()
    {
        self::assertEquals(Colour::YELLOW, Colour::code('yElloW'));
    }
}
