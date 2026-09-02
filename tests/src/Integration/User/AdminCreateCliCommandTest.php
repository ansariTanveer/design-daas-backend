<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Cli\CliApplication;
use Application\Common\Application\TestHelper\TestCliRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class AdminCreateCliCommandTest extends TestCase
{
    private EntityManagerInterface $em;
    private CliApplication $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::cli();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $existingAdmin = TestEntityBuilder::buildAdmin(['email' => 'first.admin@example.com']);
        $this->em->persist($existingAdmin);
        $this->em->flush();
        $this->em->clear();
    }

    public function testCreateAdminCommand(): void
    {
        (new TestCliRequestBuilder())
            ->argv(['admin:create', 'joeNormal', 'joe.normal@example.com', '#12345Password$'])
            ->expectExitCode(0)
            ->execute($this->application);
    }
}
