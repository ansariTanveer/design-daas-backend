<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Exception\NoRawValueException;
use Application\Core\User\Model\Password;
use Application\Test\TestCase;

final class PasswordTest extends TestCase
{
    public function testPasswordGeneratedFromPlainText(): void
    {
        $password = Password::fromPlainString('!TopSecret12345!');

        self::assertTrue($password->equals(Password::fromPlainString('!TopSecret12345!')));
        self::assertFalse($password->equals(Password::fromPlainString('!BottomSecret12345!')));

        self::assertEquals('!TopSecret12345!', $password->asPlainString());
    }

    public function testPasswordGeneratedFromHash(): void
    {
        $password = Password::fromHash('$2y$10$KImm7Ezvvl8RFiRNjbs84OiAvU6cNwbUE5uYe1GHql2ii61sW.Lky');

        self::assertTrue($password->equals(Password::fromPlainString('#123Kartoffelbrei#')));
        self::assertFalse($password->equals(Password::fromPlainString('#456InDieEck#')));

        self::assertEquals('$2y$10$KImm7Ezvvl8RFiRNjbs84OiAvU6cNwbUE5uYe1GHql2ii61sW.Lky', $password->asHash());

        self::expectException(NoRawValueException::class);
        $password->asPlainString();
    }

    public function testRejectsPasswordWithLessThen8Characters(): void
    {
        self::expectException(InvalidPasswordException::class);

        Password::fromPlainString('!top12!');
    }

    public function testRejectsPasswordWithoutUppercaseLetter(): void
    {
        self::expectException(InvalidPasswordException::class);

        Password::fromPlainString('!topsecret12345!');
    }

    public function testRejectPasswordWithoutSpecialLetter(): void
    {
        self::expectException(InvalidPasswordException::class);

        Password::fromPlainString('topsecret12345');
    }

    public function testPasswordGeneration(): void
    {
        self::expectNotToPerformAssertions();

        // generate a bunch of passwords and make sure the generation and validation functions are in sync.
        // because validatePasswordRules() will throw if validation fails, we'll know when they're out of sync.
        for ($i = 0; $i < 100; $i++) {
            $password = Password::generateNewPassword();
            Password::validatePasswordRules($password->asPlainString());
        }
    }
}
