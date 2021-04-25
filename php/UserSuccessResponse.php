<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserSuccessResponse extends UserResponse
{
    public function __construct(
        string $userMessage = 'Done.',
        int $colour = Colour::GREEN,
        string $icon = '✔ ',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($userMessage, $colour, $icon, $code, $previous);
    }
}
