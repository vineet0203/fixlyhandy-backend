<?php

namespace App\Exceptions;

use RuntimeException;

class CrossRoleEmailConflictException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $existingRole,
        int $code = 422,
    ) {
        parent::__construct($message, $code);
    }

    public function getExistingRole(): string
    {
        return $this->existingRole;
    }
}