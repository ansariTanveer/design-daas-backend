<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230905074006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            /** @lang MySQL */
            'CREATE TABLE desktops_to_desktop_groups (
                desktop_id BIGINT NOT NULL,
                desktop_group_id BIGINT NOT NULL,
                INDEX IDX_636EC211FFF2950E (desktop_id),
                INDEX IDX_636EC2112BF2573A (desktop_group_id),
                PRIMARY KEY(desktop_id, desktop_group_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE desktops_to_desktop_groups
                ADD CONSTRAINT FK_636EC211FFF2950E FOREIGN KEY (desktop_id) REFERENCES desktops (id)'
        );
        $this->addSql(
            /** @lang MySQL */
            'ALTER TABLE desktops_to_desktop_groups
            ADD CONSTRAINT FK_636EC2112BF2573A FOREIGN KEY (desktop_group_id) REFERENCES desktop_groups (id)'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */'DROP TABLE desktops_to_desktop_groups');
    }
}
