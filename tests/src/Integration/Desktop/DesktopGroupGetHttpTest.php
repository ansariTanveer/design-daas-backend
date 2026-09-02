<?php

declare(strict_types=1);

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Application\Test\TestCase;

class DesktopGroupGetHttpTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

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
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);

        $desktopTwo = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktopTwo);

        $this->em->flush();

        $desktopGroup->addDesktop($desktop);
        $desktopGroup->addDesktop($desktopTwo);
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/desktop_group/%1$d', $desktopGroup->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTO = DesktopGroupDetailsDTO::fromResponse($result->response());
        self::assertEquals($desktopGroup->id(), $resultDTO->id);
        self::assertCount(2, $resultDTO->desktopIds);
    }
}
