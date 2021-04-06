<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserResponse extends \Exception
{
    /**
     * @var int Colour code
     */
    protected $colour;

    /**
     * @var string an emoji that can be disabled.
     */
    private $icon;

    /**
     * Creates a coloured version of the exception message.
     *
     * @param bool $withIcon
     * @return string
     */
    public function message(bool $withIcon = true): string
    {
        $message = $this->getMessage();
        if ($withIcon && $this->icon) {
            $message = $this->icon . ' ' . $message;
        }
        return Colour::string($message, $this->colour);
    }

    /**
     * UserResponse constructor.
     *
     * @param string         $userMessage Printed by any B-Hayes\CLI based application.
     * @param int            $code        The exit code to use when the application terminates.
     * @param int            $colour      The colour to print the message in. (default will change colour based on code)
     * @param string         $icon        Displayed before the message if UTF-8 output is enabled.
     * @param Throwable|null $previous    Any related error to print in debug mode.
     */
    public function __construct(
        string $userMessage,
        int $colour = 0,
        string $icon = '',
        int $code = 1,
        Throwable $previous = null
    ) {
        $this->colour = $colour;
        $this->icon = $icon;
        parent::__construct($userMessage, $code, $previous);
    }
}
