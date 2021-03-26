<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserErrorResponse extends UserResponse
{
    public function __construct(
        string $userMessage,
        string $colour = 'Red',
        string $icon = '❌ ',
        int $code = 1,
        Throwable $previous = null
    ) {
        parent::__construct($userMessage, $colour, $icon, $code, $previous);
    }
}
