<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserResponse extends \Exception
{
    /**
     * @var string
     */
    protected $colour;
    /**
     * @var string
     */
    private $icon;

    public function message(bool $withColour = true, bool $withIcon = true): string
    {
        $message = $this->getMessage();
        if ($withIcon) {
            $message = $this->icon . ' ' . $message;
        }
        if ($withColour) {
            //todo: use colour
        }
        return $message;
    }

    /**
     * UserResponse constructor.
     *
     * @param string         $userMessage Printed by any B-Hayes\CLI based application.
     * @param int            $code        The exit code to use when the application terminates.
     * //todo: add support for the following params somehow:
     * @param string         $colour      The colour to print the message in. (default will change colour based on code)
     * @param string         $icon        Displayed before the message if UTF-8 output is enabled.
     * @param Throwable|null $previous    Any related error to print in debug mode.
     */
    public function __construct(
        string $userMessage,
        string $colour = 'Blue',
        string $icon = '',
        int $code = 1,
        Throwable $previous = null
    ) {
        $this->colour = $colour;
        $this->icon = $icon;
        parent::__construct($userMessage, $code, $previous);
    }
}
