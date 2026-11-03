<?php

namespace App\DTOs;

final readonly class AttemptLoginData
{
    public function __construct(public string $email, public string $password)
    {
    }
}
