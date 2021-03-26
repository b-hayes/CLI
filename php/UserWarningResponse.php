<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserWarningResponse extends UserResponse
{
    public function __construct(
        string $userMessage,
        string $colour = 'Orange',
        string $icon = '⚠ ',
        int $code = 1,
        Throwable $previous = null
    ) {
        parent::__construct($userMessage, $colour, $icon, $code, $previous);
    }
}
