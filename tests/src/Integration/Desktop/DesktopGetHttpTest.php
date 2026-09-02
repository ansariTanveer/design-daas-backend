<?php

declare(strict_types=1);

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class DesktopGetHttpTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private User $user;
    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->user = TestEntityBuilder::buildUser();
        $this->em->persist($this->user);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @throws DesktopException
     */
    public function testDesktopGet(): void
    {
        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktopGroup->addDesktop($desktop);
        $this->em->persist($desktopGroup);

        $this->em->flush();

        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/desktop/%1$d', $desktop->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTO = DesktopDetailsDTO::fromResponse($result->response());
        self::assertEquals($desktop->id(), $resultDTO->id);
        self::assertCount(1, $resultDTO->groups);
        self::assertEquals($desktopGroup->id(), $resultDTO->groups[0]);
    }
}
