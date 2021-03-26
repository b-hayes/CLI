<?php

declare(strict_types=1);

namespace BHayes\CLI;

use Throwable;

class UserSuccessResponse extends UserResponse
{
    public function __construct(
        string $userMessage = 'Finished!',
        string $colour = 'Green',
        string $icon = '✔ ',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($userMessage, $colour, $icon, $code, $previous);
    }
}
