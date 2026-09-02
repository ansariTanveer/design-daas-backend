<?php

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class DesktopListHttpTest extends TestCase
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
    }

    /**
     * @throws DesktopException
     */
    public function testAddsUserGroupToDesktopGroup(): void
    {
        $desktopOne = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktopOne);

        $desktopTwo = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktopTwo);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktopGroup->addDesktop($desktopOne);
        $desktopGroup->addDesktop($desktopTwo);
        $this->em->persist($desktopGroup);

        $desktopThree = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktopThree);

        $desktopGroupTwo = TestEntityBuilder::buildDesktopGroup();
        $desktopGroupTwo->addDesktop($desktopThree);
        $this->em->persist($desktopGroupTwo);

        $this->em->flush();

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri('/desktops')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseDTOList = DesktopDetailsDTO::fromArrayResponse($responseContainer->response());

        self::assertCount(3, $responseDTOList);
        self::assertContainsOnlyInstancesOf(DesktopDetailsDTO::class, $responseDTOList);
    }
}
