<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopGroupDetailsDtoTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function testDesktopGroupDetailsDto(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);
        $desktopGroup->addDesktop($desktop);

        $this->em->flush();
        $this->em->refresh($desktopGroup);

        $dto = DesktopGroupDetailsDto::fromEntity($desktopGroup);
        self::assertEquals($desktopGroup->id(), $dto->id);
        self::assertEquals($desktopGroup->description(), $dto->description);
        self::assertCount(1, $dto->desktopIds);
        self::assertEquals($desktopGroup->desktops()[0]->id(), $dto->desktopIds[0]);
    }
}
