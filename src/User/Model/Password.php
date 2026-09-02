<?php

namespace Application\Core\User\Model;

use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Exception\NoRawValueException;

class Password
{
    private const MIN_PASSWORD_LENGTH = 8;

    private string $password;

    private bool $hashed;

    private function __construct(string $password, bool $hashed)
    {
        $this->password = $password;
        $this->hashed = $hashed;
    }

    /**
     * @throws InvalidPasswordException
     */
    public static function validatePasswordRules(string $plainTextPassword): void
    {
        /*
         * This method will throw an exception if the password
         * isn't at least 8 characters long or is missing an
         * uppercase letter or a special character
         * note: "special character" is defined as "anything beyond upper/lowercase A-Z and 0-9"
         */
        if (strlen($plainTextPassword) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidPasswordException();
        }

        $hasUppercaseLetter = preg_match('/[A-Z]/', $plainTextPassword) > 0;
        if (!$hasUppercaseLetter) {
            throw new InvalidPasswordException();
        }

        // remove all instances of A-Z (case insensitive) and 0-9. what remains is considered a special character
        $passwordFilteredForSpecialLetters = preg_replace('/[A-Z0-9]/i', "", $plainTextPassword);
        assert(is_string($passwordFilteredForSpecialLetters));
        if (strlen($passwordFilteredForSpecialLetters) === 0) {
            throw new InvalidPasswordException();
        }
    }

    /**
     * @throws InvalidPasswordException
     */
    public static function fromPlainString(string $password): self
    {
        self::validatePasswordRules($password);
        return new self($password, false);
    }

    public static function generateNewPassword(): self
    {
        $alphabet = array_merge(range("a", "z"));
        $special = ["!", "#", "$", "%", "&", "(", ")", "*", "+", "-", ".", ":"];
        $generatedPassword = "";

        // generate random all-letters password base
        $alphabetCount = count($alphabet);
        for ($i = 0; $i < self::MIN_PASSWORD_LENGTH; $i++) {
            $alphabetIdx = rand(0, $alphabetCount - 1);
            $generatedPassword .= $alphabet[$alphabetIdx];
        }

        // make one of the letters uppercase
        $uppercaseIdx = rand(0, self::MIN_PASSWORD_LENGTH - 1);
        $generatedPassword[$uppercaseIdx] = strtoupper($generatedPassword[$uppercaseIdx]);

        // replace one of the other letters with a special character
        // do this in a short loop so we don't replace the character we just uppercased
        $punctuationIdx = -1;
        while ($punctuationIdx < 0 || $punctuationIdx === $uppercaseIdx) {
            $punctuationIdx = rand(0, self::MIN_PASSWORD_LENGTH - 1);
        }
        $generatedPassword[$punctuationIdx] = $special[rand(0, count($special) - 1)];

        return self::fromPlainString($generatedPassword);
    }

    public static function fromHash(string $password): self
    {
        return new self($password, true);
    }

    public function isHashed(): bool
    {
        return $this->hashed;
    }

    public function asHash(): string
    {
        if ($this->hashed) {
            return $this->password;
        }

        return password_hash($this->password, PASSWORD_DEFAULT);
    }

    /**
     * @throws NoRawValueException
     */
    public function asPlainString(): string
    {
        if ($this->hashed) {
            throw new NoRawValueException();
        }

        return $this->password;
    }

    public function equals(Password $password): bool
    {
        if ($this->isHashed() && $password->isHashed()) {
            return $this->asHash() === $password->asHash();
        }

        if ($password->isHashed()) {
            return password_verify($this->asPlainString(), $password->asHash());
        }

        return password_verify($password->asPlainString(), $this->asHash());
    }
}
