<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountService
{
    public function createStudentAccount(
        string $fullName,
        string $studentNumber
    ): User
    {
        $email = $this->generateUniqueEmail(
            'siswa.' . Str::lower($studentNumber)
        );

        $user = User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make($studentNumber),
        ]);

        $user->assignRole('Siswa');

        return $user;
    }

    public function createParentAccount(
        string  $fullName,
        ?string $phone = null
    ): User
    {
        $identifier = !empty($phone)
            ? preg_replace('/[^0-9]/', '', $phone)
            : Str::lower(Str::random(12));

        $email = $this->generateUniqueEmail(
            'orangtua.' . $identifier
        );

        $user = User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make(
                $this->generateInitialPassword($fullName)
            ),
        ]);

        $user->assignRole('Orang Tua');

        return $user;
    }

    public function updateAccount(
        User   $user,
        string $fullName
    ): User
    {
        $user->update([
            'name' => $fullName,
        ]);

        return $user->refresh();
    }

    public function deactivateAccount(User $user): User
    {
        return $user;
    }

    public function activateAccount(User $user): User
    {
        return $user;
    }

    private function generateUniqueEmail(string $prefix): string
    {
        $email = $prefix . '@bimbel.test';

        if (!User::where('email', $email)->exists()) {
            return $email;
        }

        return $prefix . '.' . Str::lower(
                Str::random(6)
            ) . '@bimbel.test';
    }

    private function generateInitialPassword(
        string $fullName
    ): string
    {
        $name = Str::of($fullName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->substr(0, 6)
            ->toString();

        if ($name === '') {
            $name = 'bimbel';
        }

        return $name . '12345';
    }
}
