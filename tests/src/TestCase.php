<?php

declare(strict_types=1);

namespace Application\Test;

use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use BjoernGoetschke\DateTime\Moment;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Contracts\Service\ResetInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public const TEST_TMP_DIR = __DIR__ . '/../assets/tmp';
    public const TEST_CLIENT_ID = 'phpunit-client-id';
    public const TEST_CLIENT_SECRET = 'phpunit-client-secret';
    public const USER_TEST_TOKEN_PREFIX = 'phpunit-user';

    protected function tearDown(): void
    {
        parent::tearDown();
        self::clearTestTmpFiles();
        TestDatabaseConnectionFactory::reset();
        Moment::resetNow();

        /*
         * Automatically cleanup properties of child classes
         */
        $class = new ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            if (!is_subclass_of($property->class, self::class) || $property->isStatic()) {
                continue;
            }
            $type = $property->getType();
            if ($type === null || $type->allowsNull()) {
                $property->setValue($this, null);
            } elseif ($property->isInitialized($this)) {
                $value = $property->getValue($this);
                if ($value instanceof ResetInterface) {
                    $value->reset();
                }
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        /*
         * Automatically cleanup properties of child classes
         */
        $class = new ReflectionClass(static::class);
        foreach ($class->getProperties() as $property) {
            if (!is_subclass_of($property->class, self::class) || !$property->isStatic()) {
                continue;
            }
            $type = $property->getType();
            if ($type === null || $type->allowsNull()) {
                $property->setValue(null);
            } elseif ($property->isInitialized()) {
                $value = $property->getValue();
                if ($value instanceof ResetInterface) {
                    $value->reset();
                }
            }
        }
    }

    public static function clearTestTmpFiles(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::TEST_TMP_DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            assert($file instanceof SplFileInfo);
            $path = $file->getPathname();
            if ($path === self::TEST_TMP_DIR . '/.gitignore') {
                continue;
            }
            if ($file->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }

    /**
     * @param list<string> $scopes
     * @return array<string, string>
     */
    public static function authorizeUser(User $user, array $scopes = ['user']): array
    {
        return self::authorizeTestToken(self::USER_TEST_TOKEN_PREFIX, $user->id(), $scopes);
    }

    /**
     * @param list<string> $scopes
     * @return array<string, string>
     */
    public static function authorizeAdmin(Admin $admin, array $scopes = ['admin']): array
    {
        return self::authorizeTestToken(self::USER_TEST_TOKEN_PREFIX, $admin->id(), $scopes);
    }

    /**
     * @param list<string> $scopes
     * @return array<string, string>
     */
    private static function authorizeTestToken(string $testTokenPrefix, int $userId, array $scopes = []): array
    {
        return [
            'HTTP_AUTHORIZATION' => sprintf(
                '%1$s:%2$d:%3$s',
                $testTokenPrefix,
                $userId,
                implode(' ', $scopes)
            )
        ];
    }
}
