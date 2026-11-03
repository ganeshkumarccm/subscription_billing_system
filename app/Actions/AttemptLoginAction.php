<?php

namespace App\Actions;

use App\DTOs\AttemptLoginData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AttemptLoginAction
{
    public function execute(AttemptLoginData $data): User
    {
        $user = User::query()->where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $user;
    }
}
